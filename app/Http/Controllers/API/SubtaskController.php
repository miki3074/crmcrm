<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subtask;
use App\Models\Task;

use App\Models\SubtaskFile;
use Illuminate\Support\Facades\Storage;

class SubtaskController extends Controller
{
    public function index(Task $task)
    {
        $this->authorize('view', $task);

       $subtasks = $task->subtasks()
    ->with(['creator:id,name', 'executors:id,name', 'responsibles:id,name'])
    ->get();


        return response()->json($subtasks);
    }

public function store(Request $request, Task $task)
{
    $this->authorize('createSubtask', $task);

    // Преобразуем одиночные ID в массивы
    $data = $request->all();
    if (!is_array($data['executor_id'] ?? null)) {
        $data['executor_id'] = [$data['executor_id']];
    }
    if (!is_array($data['responsible_id'] ?? null)) {
        $data['responsible_id'] = [$data['responsible_id']];
    }

    $validated = validator($data, [
        'title'           => 'required|string|max:255',
        'executor_id'     => 'required|array|min:1',
        'executor_id.*'   => 'exists:users,id',
        'responsible_id'  => 'required|array|min:1',
        'responsible_id.*'=> 'exists:users,id',
        'start_date'      => 'required|date',
        'due_date'        => 'required|date|after_or_equal:start_date',
        'parent_id'       => 'nullable|exists:subtasks,id',
    ])->validate();

    $subtask = $task->subtasks()->create([
        'title'      => $validated['title'],
        'start_date' => $validated['start_date'],
        'due_date'   => $validated['due_date'],
        'creator_id' => auth()->id(),
        'parent_id'  => $data['parent_id'] ?? null,
    ]);

    // Привязываем пользователей
    $subtask->executors()->sync($validated['executor_id']);
    $subtask->responsibles()->sync($validated['responsible_id']);

    // Уведомления
    $recipients = array_unique(array_merge(
        $validated['executor_id'],
        $validated['responsible_id']
    ));

    foreach ($recipients as $userId) {
        $user = \App\Models\User::find($userId);
        if ($user && $user->telegram_chat_id) {
            \App\Services\TelegramService::sendMessage(
                $user->telegram_chat_id,
                "🆕 Вам назначена новая подзадача: <b>{$subtask->title}</b>\n".
                "Задача: {$task->title}\n".
                "Срок: {$subtask->due_date}"
            );
        }
    }

    return response()->json(
        $subtask->load(['executors:id,name', 'responsibles:id,name', 'creator:id,name']),
        201
    );
}







    public function show(Subtask $subtask)
    {
        $subtask->load([
    'task.project.company',
    'task.project.executors:id,name',   // 👈 добавляем
        'task.project.managers:id,name',    // 👈 добавляем
    'creator:id,name',
    'executors:id,name',
    'responsibles:id,name',
    'files.user:id,name',
    'children.executors:id,name', // 👈 подгружаем дочерние подзадачи
        'children.responsibles:id,name',
        'children.files.user:id,name',
]);


        $this->authorize('view', $subtask);

        return response()->json($subtask);
    }

    public function updateProgress(Request $request, Subtask $subtask)
    {
        $this->authorize('updateProgress', $subtask);

        $data = $request->validate([
            'progress' => 'required|integer|min:0|max:100',
        ]);

        $subtask->update(['progress' => $data['progress']]);

        return response()->json([
            'message'  => 'Прогресс обновлён',
            'progress' => $subtask->progress,
        ]);
    }

    public function complete(Request $request, Subtask $subtask)
    {
        $this->authorize('complete', $subtask);

        if ((int) $subtask->progress < 100) {
            return response()->json([
                'message' => 'Подзадачу можно завершить только при прогрессе 100%.'
            ], 422);
        }

        if ($subtask->completed) {
            return response()->json([
                'message'      => 'Подзадача уже завершена.',
                'completed'    => true,
                'completed_at' => $subtask->completed_at,
            ]);
        }

        $subtask->forceFill([
            'completed'    => true,
            'completed_at' => now(),
        ])->save();

        return response()->json([
            'message'      => 'Подзадача завершена.',
            'completed'    => true,
            'completed_at' => $subtask->completed_at,
        ]);
    }


public function destroy(Subtask $subtask)
{
    $this->authorize('delete', $subtask);

    // удаляем подзадачу
    $subtask->delete();

    return response()->json(['message' => 'Подзадача успешно удалена']);
}


// 🔹 Изменить ответственного
public function changeResponsible(Request $request, Subtask $subtask)
{
    $this->authorize('manageMembers', $subtask);

    $data = $request->validate([
        'user_id' => 'required|exists:users,id',
        'replace_user_id' => 'nullable|exists:users,id',
    ]);

    // ⚙️ Проверка: новый пользователь не должен уже быть ответственным
    if ($subtask->responsibles()->where('user_id', $data['user_id'])->exists()) {
        return response()->json([
            'message' => 'Этот пользователь уже является ответственным.',
        ], 422);
    }

    // ⚙️ Если есть кого заменить — удаляем только его
    if (!empty($data['replace_user_id'])) {
        $subtask->responsibles()->detach($data['replace_user_id']);
    }

    // ⚙️ Добавляем нового без удаления остальных
    $subtask->responsibles()->syncWithoutDetaching([$data['user_id']]);

    return response()->json([
        'message' => 'Ответственный успешно изменён.',
        'responsibles' => $subtask->responsibles()->get(['users.id', 'users.name']),
    ]);
}

// 🔹 Изменить исполнителя
// 🔹 Изменить исполнителя (точечная замена)
public function changeExecutor(Request $request, Subtask $subtask)
{
    $this->authorize('manageMembers', $subtask);

    $data = $request->validate([
        'user_id' => 'required|exists:users,id',
        'replace_user_id' => 'nullable|exists:users,id',
    ]);

    // ⚙️ Проверка: новый пользователь не должен уже быть исполнителем
    if ($subtask->executors()->where('user_id', $data['user_id'])->exists()) {
        return response()->json([
            'message' => 'Этот пользователь уже является исполнителем.',
        ], 422);
    }

    // ⚙️ Если передан заменяемый — удаляем только его
    if (!empty($data['replace_user_id'])) {
        $subtask->executors()->detach($data['replace_user_id']);
    }

    // ⚙️ Добавляем нового
    $subtask->executors()->syncWithoutDetaching([$data['user_id']]);

    return response()->json([
        'message' => 'Исполнитель успешно изменён.',
        'executors' => $subtask->executors()->get(['users.id', 'users.name']),
    ]);
}


// 🔹 Добавить исполнителя
public function addExecutors(Request $request, Subtask $subtask)
{
    $this->authorize('manageMembers', $subtask);

    $data = $request->validate([
        'user_ids' => 'required|array|min:1',
        'user_ids.*' => 'exists:users,id',
    ]);

    $subtask->executors()->syncWithoutDetaching($data['user_ids']);

    return response()->json([
        'message' => 'Исполнители добавлены',
    'executors' => $subtask->executors()->get(['users.id', 'users.name']),
    ]);
}

// 🔹 Добавить ответственного
public function addResponsibles(Request $request, Subtask $subtask)
{
    $this->authorize('manageMembers', $subtask);

    $data = $request->validate([
        'user_ids' => 'required|array|min:1',
        'user_ids.*' => 'exists:users,id',
    ]);

    $subtask->responsibles()->syncWithoutDetaching($data['user_ids']);

    return response()->json([
        'message' => 'Ответственные добавлены',
    'responsibles' => $subtask->responsibles()->get(['users.id', 'users.name']),
    ]);
}

// Удалить исполнителя
public function removeExecutor(Request $request, Subtask $subtask)
{
    $this->authorize('manageMembers', $subtask);

    $data = $request->validate([
        'user_id' => 'required|exists:users,id',
    ]);

    // Проверим, что хотя бы один исполнитель останется
    if ($subtask->executors()->count() <= 1) {
        return response()->json([
            'message' => 'Нельзя удалить всех исполнителей. В подзадаче должен остаться хотя бы один.',
        ], 422);
    }

    $subtask->executors()->detach($data['user_id']);

    return response()->json([
        'message' => 'Исполнитель удалён',
        'executors' => $subtask->executors()->get(['users.id', 'users.name']),
    ]);
}

// Удалить ответственного
public function removeResponsible(Request $request, Subtask $subtask)
{
    $this->authorize('manageMembers', $subtask);

    $data = $request->validate([
        'user_id' => 'required|exists:users,id',
    ]);

    // Проверим, что хотя бы один ответственный останется
    if ($subtask->responsibles()->count() <= 1) {
        return response()->json([
            'message' => 'Нельзя удалить всех ответственных. В подзадаче должен остаться хотя бы один.',
        ], 422);
    }

    $subtask->responsibles()->detach($data['user_id']);

    return response()->json([
        'message' => 'Ответственный удалён',
        'responsibles' => $subtask->responsibles()->get(['users.id', 'users.name']),
    ]);
}

public function update(Request $request, Subtask $subtask)
{
    $this->authorize('update', $subtask);

    $validated = $request->validate([
    'title' => 'required|string|max:255',
    'due_date' => 'required|date|after_or_equal:' . $subtask->start_date,
], [
    'title.required' => 'Введите название',
    'due_date.required' => 'Введите дату окончания',
    'due_date.after_or_equal' => 'Дата окончания не может быть раньше даты начала',
]);

    $subtask->update($validated);

    return response()->json([
        'message' => 'Подзадача обновлена успешно',
        'subtask' => $subtask->fresh(['executors:id,name', 'responsibles:id,name']),
    ]);
}

public function uploadFile(Request $request, Subtask $subtask)
{
    $this->authorize('addFiles', $subtask);

    $request->validate([
        'file' => 'required|file|max:10240', // до 10 МБ
    ]);

    $file = $request->file('file');
    $path = $file->store('subtask_files', 'public');

    $subtaskFile = $subtask->files()->create([
        'user_id' => auth()->id(),
        'filename' => $file->getClientOriginalName(),
        'path' => $path,
    ]);

    return response()->json($subtaskFile, 201);
}

public function downloadFile(SubtaskFile $file)
{
    return Storage::disk('public')->download($file->path, $file->filename);
}

public function deleteFile(SubtaskFile $file)
{
    $subtask = $file->subtask;
    $this->authorize('addFiles', $subtask);

    Storage::disk('public')->delete($file->path);
    $file->delete();

    return response()->json(['message' => 'Файл удалён']);
}


// app/Http/Controllers/SubtaskController.php
public function storeChild(Request $request, Subtask $subtask)
{
   $this->authorize('createSubtask', $subtask);

    $data = $request->validate([
        'title' => 'required|string|max:255',
        'start_date' => 'nullable|date',
        'due_date' => 'nullable|date',
        'executor_ids' => 'required|array|min:1',
        'executor_ids.*' => 'exists:users,id',
        'responsible_ids' => 'required|array|min:1',
        'responsible_ids.*' => 'exists:users,id',
    ]);

    $child = $subtask->children()->create([
        'title'      => $data['title'],
        'start_date' => $data['start_date'] ?? now(),
        'due_date'   => $data['due_date'],
        'creator_id' => auth()->id(),
        'task_id'    => $subtask->task_id,
        'parent_id'  => $subtask->id,
    ]);

    $child->executors()->sync($data['executor_ids']);
    $child->responsibles()->sync($data['responsible_ids']);

    return response()->json($child->load(['executors:id,name', 'responsibles:id,name']), 201);
}







}
