<?php

namespace App\Http\Controllers;

use App\Models\Klient;
use App\Models\KlientTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Models\User;
use App\Models\KlientTaskFile;

class KlientTaskController extends Controller
{
    /**
     * Сохранение новой задачи
     */
    public function store(Request $request, Klient $klient)
    {
        // Проверка доступа к клиенту (аналогично предыдущему ответу)
        $userId = Auth::id();
        $hasAccess = $klient->user_id === $userId || $klient->allowedUsers()->where('users.id', $userId)->exists();
        if (!$hasAccess) abort(403);

        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'responsible_id' => 'required|exists:users,id',
            'deadline'       => 'nullable|date',
            'priority'       => 'required|in:high,medium,low',
            'type'           => 'required|string',
            'files.*'        => 'nullable|file|max:10240', // проверка каждого файла
        ]);

        return DB::transaction(function () use ($validated, $klient, $request) {
            // 1. Создаем задачу
            $task = $klient->tasks()->create([
                'creator_id'     => Auth::id(),
                'responsible_id' => $validated['responsible_id'],
                'title'          => $validated['title'],
                'description'    => $validated['description'],
                'deadline'       => $validated['deadline'],
                'priority'       => $validated['priority'],
                'type'           => $validated['type'],
                'status'         => 'pending',
            ]);

            // 2. Загружаем файлы, если они есть
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $path = $file->store('klient_tasks/' . $task->id);
                    $task->files()->create([
                        'user_id'       => Auth::id(),
                        'original_name' => $file->getClientOriginalName(),
                        'file_path'     => $path,
                        'file_size'     => $file->getSize(),
                    ]);
                }
            }

            return back()->with('success', 'Задача создана');
        });
    }

    /**
     * Смена статуса задачи (выполнена/в работе)
     */
    public function toggleStatus(KlientTask $task)
    {
        // Проверяем права
        $user = auth()->user();

        if ($task->responsible_id !== $user->id && $task->created_by !== $user->id) {
            return back()->with('error', 'У вас нет прав на изменение статуса этой задачи');
        }

        $task->update([
            'status' => $task->status === 'completed' ? 'pending' : 'completed'
        ]);

        return back();
    }

    public function updateStatus(Request $request, KlientTask $task)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled'
        ]);

        // Проверяем, имеет ли пользователь право менять статус
        $user = auth()->user();

        if ($task->responsible_id !== $user->id && $task->created_by !== $user->id) {
            return back()->with('error', 'У вас нет прав на изменение статуса этой задачи');
        }

        $task->update(['status' => $validated['status']]);

        return back()->with('success', 'Статус задачи обновлен');
    }

    public function show(KlientTask $task)
    {
        // Загружаем всё необходимое для страницы задачи
        $task->load(['klient', 'responsible', 'creator', 'files']);

        $users = User::where('id', '!=', auth()->id())
            ->orderBy('name')
            ->select('id', 'name', 'email')
            ->get();

        $canEdit = $task->creator_id === auth()->id();

        return Inertia::render('Klients/Tasks/Show', [
            'task' => $task,
            'user' => auth()->user(),
            'klient' => $task->klient,
            'users' => $users,
            'canEdit' => $canEdit
        ]);
    }

    // KlientTaskController.php

    public function update(Request $request, Klient $klient, KlientTask $task)
    {
        // Проверка доступа к клиенту
        $userId = Auth::id();
        $hasAccess = $klient->user_id === $userId || $klient->allowedUsers()->where('users.id', $userId)->exists();
        if (!$hasAccess) abort(403);

        // Проверка прав на редактирование задачи (только ответственный или создатель)
        if ($task->creator_id !== $userId) {
            abort(403, 'Только создатель задачи может редактировать её');
        }

        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'responsible_id' => 'required|exists:users,id',
            'deadline'       => 'nullable|date',
            'priority'       => 'required|in:high,medium,low',
            'type'           => 'required|string',
            'files.*'        => 'nullable|file|max:10240',
        ]);

        return DB::transaction(function () use ($validated, $task, $request) {
            // 1. Обновляем задачу
            $task->update([
                'title'          => $validated['title'],
                'description'    => $validated['description'],
                'responsible_id' => $validated['responsible_id'],
                'deadline'       => $validated['deadline'],
                'priority'       => $validated['priority'],
                'type'           => $validated['type'],
            ]);

            // 2. Добавляем новые файлы
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $path = $file->store('klient_tasks/' . $task->id);
                    $task->files()->create([
                        'user_id'       => Auth::id(),
                        'original_name' => $file->getClientOriginalName(),
                        'file_path'     => $path,
                        'file_size'     => $file->getSize(),
                    ]);
                }
            }

            // 3. Если нужно удалить файлы (опционально)
            if ($request->has('remove_files')) {
                $removeFileIds = json_decode($request->remove_files, true);
                foreach ($removeFileIds as $fileId) {
                    $file = $task->files()->find($fileId);
                    if ($file) {
                        Storage::delete($file->file_path);
                        $file->delete();
                    }
                }
            }

            return back()->with('success', 'Задача обновлена');
        });
    }

// Метод для удаления файла (опционально)
    public function deleteFile(KlientTask $task, $fileId)
    {
        $userId = Auth::id();

        // Проверка прав
        if ($task->responsible_id !== $userId && $task->creator_id !== $userId) {
            abort(403);
        }

        $file = $task->files()->findOrFail($fileId);
        Storage::delete($file->file_path);
        $file->delete();

        return response()->json(['message' => 'Файл удален']);
    }

    public function upload(Request $request, KlientTask $task)
    {
        // Проверка прав: только ответственный или создатель
        $userId = Auth::id();
        if ($task->responsible_id !== $userId && $task->creator_id !== $userId) {
            abort(403, 'У вас нет прав на добавление файлов к этой задаче');
        }

        $request->validate([
            'files.*' => 'required|file|max:10240', // максимум 10MB на файл
        ]);

        $uploadedFiles = [];

        foreach ($request->file('files') as $file) {
            $path = $file->store('klient_tasks/' . $task->id . '/files', 'public');

            $taskFile = $task->files()->create([
                'user_id' => Auth::id(),
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);

            $uploadedFiles[] = $taskFile;
        }

        return response()->json([
            'message' => 'Файлы успешно загружены',
            'files' => $uploadedFiles
        ]);
    }

    /**
     * Скачивание файла
     */
    public function download(KlientTaskFile $file)
    {
        $task = $file->klientTask;

        // Проверка прав: только ответственный, создатель или пользователи с доступом к клиенту
        $userId = Auth::id();
        $hasAccess = $task->responsible_id === $userId ||
            $task->creator_id === $userId ||
            $task->klient->user_id === $userId ||
            $task->klient->allowedUsers()->where('users.id', $userId)->exists();

        if (!$hasAccess) {
            abort(403, 'У вас нет прав на скачивание этого файла');
        }

        $filePath = storage_path('app/public/' . $file->file_path);

        if (!file_exists($filePath)) {
            abort(404, 'Файл не найден');
        }

        return response()->download($filePath, $file->original_name);
    }

    /**
     * Удаление файла
     */
    public function destroy(KlientTaskFile $file)
    {
        $task = $file->klientTask;

        // Проверка прав: только ответственный или создатель
        $userId = Auth::id();
        if ($task->responsible_id !== $userId && $task->creator_id !== $userId) {
            abort(403, 'У вас нет прав на удаление этого файла');
        }

        // Удаляем физический файл
        Storage::disk('public')->delete($file->file_path);

        // Удаляем запись из БД
        $file->delete();

        return response()->json(['message' => 'Файл успешно удален']);
    }

    /**
     * Массовое удаление файлов
     */
    public function destroyMultiple(Request $request, KlientTask $task)
    {
        // Проверка прав
        $userId = Auth::id();
        if ($task->responsible_id !== $userId && $task->creator_id !== $userId) {
            abort(403, 'У вас нет прав на удаление файлов');
        }

        $request->validate([
            'file_ids' => 'required|array',
            'file_ids.*' => 'exists:klient_task_files,id'
        ]);

        $files = $task->files()->whereIn('id', $request->file_ids)->get();

        foreach ($files as $file) {
            Storage::disk('public')->delete($file->file_path);
            $file->delete();
        }

        return response()->json(['message' => 'Файлы успешно удалены']);
    }




}
