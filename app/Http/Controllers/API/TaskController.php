<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\SendAssignedNotifications;
use App\Jobs\SendRemovedNotifications;
use App\Jobs\SendTaskAssignedNotifications;
use Illuminate\Http\Request;

use App\Models\Task;
use App\Models\TaskFile;
use App\Models\FileComment;


use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Auth;

use Illuminate\Validation\ValidationException;

use Carbon\Carbon;
class TaskController extends Controller
{

public function store(Request $request)
{

  $messages = [
        'title.required' => 'Введите название задачи.',
        'title.string'   => 'Название проекта должно быть строкой.',
        'title.max'      => 'Название проекта не может быть длиннее 255 символов.',


        'priority.required' => 'Выберите приоритет.',
        'start_date.required' => 'Укажите дату начала.',
        'due_date.required' => 'Укажите дату окончания.',
        'due_date.after_or_equal' => 'Дата окончания не может быть раньше даты начала.',
        'executor_ids.required' => 'Выберите хотя бы одного исполнителя.',
        'executor_ids.min' => 'Выберите хотя бы одного исполнителя.',
        'executor_ids.*.exists' => 'Один из выбранных исполнителей не найден.',
        'responsible_ids.required' => 'Выберите хотя бы одного ответственного.',
        'responsible_ids.min' => 'Выберите хотя бы одного ответственного.',
        'responsible_ids.*.exists' => 'Один из выбранных ответственных не найден.',
      'files.*.mimes' => 'Можно загружать файлы PDF, Word, Excel или PowerPoint.',

      'files.*.max' => 'Размер каждого файла не должен превышать 5 МБ.',
    ];

    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'priority' => 'required|in:low,medium,high',
        'start_date' => 'required|date',
        'due_date' => 'required|date|after_or_equal:start_date',
        'executor_ids' => 'required|array|min:1',
        'executor_ids.*' => 'exists:users,id',
        'responsible_ids' => 'required|array|min:1',
        'responsible_ids.*' => 'exists:users,id',
        'project_id' => 'nullable|exists:projects,id',
        'subproject_id' => 'nullable|exists:subprojects,id',
        'company_id' => 'nullable|exists:companies,id',
        'files.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:5120',

    ], $messages);

    // Определяем проект
    if (!empty($validated['subproject_id'])) {
        $subproject = \App\Models\Subproject::with('project.company')->findOrFail($validated['subproject_id']);
        $validated['project_id'] = $subproject->project->id;
        $validated['company_id'] = $subproject->project->company_id;
    } else {
        $project = \App\Models\Project::with('company')->findOrFail($validated['project_id']);
    }

    // Проверка прав
    $this->authorize('createTask', $project ?? $subproject->project);

    // Создание задачи
    $task = Task::create([
        'title' => $validated['title'],
         'description' => $validated['description'] ?? null,
        'priority' => $validated['priority'],
        'start_date' => $validated['start_date'],
        'due_date' => $validated['due_date'],
        'project_id' => $validated['project_id'],
        'company_id' => $validated['company_id'],
        'creator_id' => auth()->id(),
    ]);

    // Привязка исполнителей и ответственных
    $task->executors()->attach($validated['executor_ids']);
    $task->responsibles()->attach($validated['responsible_ids']);

   $recipients = array_unique(array_merge(
        $validated['executor_ids'],
        $validated['responsible_ids']
    ));

    $taskUrl = url("/tasks/{$task->id}");
    foreach ($recipients as $userId) {
        $user = \App\Models\User::find($userId);

        if ($user && $user->telegram_chat_id) {

            \App\Services\TelegramService::sendMessage(
                $user->telegram_chat_id,
                "🆕 Вам назначена новая задача: <b>{$task->title}</b>\n" .
                "🔗 <a href=\"{$taskUrl}\">Открыть задачу</a>\n\n" .
                "Приоритет: {$task->priority}\n" .
                "Срок: {$task->due_date}"
            );
        }
    }

    SendTaskAssignedNotifications::dispatch($task, $recipients);

    // Файлы
    if ($request->hasFile('files')) {
    foreach ($request->file('files') as $file) {
        // получаем оригинальное имя файла
        $originalName = $file->getClientOriginalName();

        // сохраняем с этим именем
        $path = $file->storeAs('task_files', $originalName, 'public');

        // сохраняем в БД путь и оригинальное имя
        $task->files()->create([
            'file_path' => $path,
            'file_name' => $originalName,

        ]);
    }
}

    return response()->json(
        $task->load(['executors', 'responsibles']),
        201
    );
}




    public function show($id)
    {
        // Добавляем withoutGlobalScope, чтобы найти задачу, даже если она завершена
        $task = Task::withoutGlobalScope('not_completed')
            ->with([
                'project.company:id,name,user_id',
                'creator:id,name',
                'executors:id,name',
                'responsibles:id,name',
                'project:id,name,company_id',
                'project.managers:id,name',
                'project.company:id,name',
                'project.watchers:id,name',
                'klients:id,name,status,phone,email,company_id,project_id,task_id',
                'klients.company:id,name',
                'files' => function ($query) {
                    $query->select('id', 'task_id', 'file_path', 'user_id', 'file_name', 'status', 'rejection_reason', 'created_at', 'updated_at')
                        ->with(['comments' => function ($q) {
                            $q->with('user:id,name')
                                ->orderBy('created_at', 'desc');
                        }, 'user:id,name']);
                },
                'watcherstask:id,name',
                'subtasks:id,task_id,title,creator_id,start_date,due_date,progress,completed',
                'subtasks.executors:id,name',
                'subtasks.creator:id,name',
                'producers:id,name,company_id',
                'buyers:id,name,company_id',
            ])->findOrFail($id);

        $this->authorize('view', $task);

        // ВАЖНО: Если это переход на страницу в браузере (Inertia),
        // то возвращать JSON нельзя, нужно возвращать Inertia::render.
        // Если у вас SPA и это API запрос, то оставьте response()->json($task).

        // Пример для Inertia (если это отдельная страница):
        // return Inertia::render('Tasks/Show', ['task' => $task]);

        return response()->json($task);
    }


public function updateProgress(Request $request, Task $task)
{
    $this->authorize('updateProgress', $task); // если есть политика

    $validated = $request->validate([
        'progress' => 'required|integer|min:0|max:100',
    ]);

    $task->update(['progress' => $validated['progress']]);

    return response()->json(['message' => 'Прогресс обновлен', 'progress' => $task->progress]);
}



    public function addFiles(Request $request, Task $task)
    {
        $this->authorize('addFiles', $task);

        $allowedExtensions = [
            'pdf',
            'doc',
            'docx',
            'xls',
            'xlsx',
            'ppt',
            'pptx',
            'zip',
            'rar',
            'mp3',
            'wav',
            'ogg',
            'flac',
            'm4a',
            'aac',
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp',
        ];

        $request->validate([
            'files' => [
                'required',
                'array',
                'min:1',
            ],

            'files.*' => [
                'required',
                'file',
                'max:102400',

                function ($attribute, $file, $fail) use ($allowedExtensions) {
                    $extension = strtolower(
                        $file->getClientOriginalExtension()
                    );

                    if (!in_array($extension, $allowedExtensions, true)) {
                        $fail(
                            'Недопустимый формат файла: ' .
                            $file->getClientOriginalName()
                        );
                    }
                },
            ],

            'requires_approval' => [
                'nullable',
                'boolean',
            ],
        ], [
            'files.required' => 'Выберите хотя бы один файл',
            'files.array' => 'Поле files должно быть массивом',
            'files.min' => 'Выберите хотя бы один файл',
            'files.*.required' => 'Файл не был передан',
            'files.*.file' => 'Загруженный объект не является файлом',
            'files.*.max' => 'Файл не должен превышать 100 МБ',
        ]);

        $status = $request->boolean('requires_approval')
            ? 'pending'
            : 'none';

        foreach ($request->file('files', []) as $file) {
            $originalName = $file->getClientOriginalName();

            $path = $file->store('task_files', 'public');

            $task->files()->create([
                'file_path' => $path,
                'file_name' => $originalName,
                'user_id' => auth()->id(),
                'status' => $status,
            ]);
        }

        return response()->json([
            'message' => 'Файлы успешно загружены',
        ]);
    }



    public function downloadFile($fileId)
    {
        $file = \App\Models\TaskFile::findOrFail($fileId);

        // 1. Проверка прав
        $this->authorize('view', $file->task);

        // 2. Проверка существования
        if (!Storage::disk('public')->exists($file->file_path)) {
            abort(404, 'Файл не найден');
        }

        // 3. Скачивание
        // Первый аргумент: путь на диске
        // Второй аргумент: имя, под которым файл скачается у пользователя
        return Storage::disk('public')->download(
            $file->file_path,
            $file->file_name // Используем оригинальное имя из БД
        );
    }




public function deleteFile($fileId)
{
    $file = \App\Models\TaskFile::findOrFail($fileId);

    // проверяем доступ
    $this->authorize('deleteFile', $file->task);

    $path = $file->file_path;

    if (Storage::disk('public')->exists($path)) {
        Storage::disk('public')->delete($path);
    }

    $file->delete();

    return response()->json(['message' => 'Файл удалён']);
}










public function complete(Task $task)
    {
        $this->authorize('complete', $task);

        // Притянем подзадачи (чтобы не попасть в N+1 при фронтовом show)
        $task->loadMissing('subtasks:id,task_id,completed');

        if ((int)$task->progress < 100) {
            throw ValidationException::withMessages([
                'progress' => 'Задачу можно завершить только при прогрессе 100%.',
            ]);
        }

        $hasOpenSubtasks = $task->subtasks()->where('completed', false)->exists();
        if ($hasOpenSubtasks) {
            throw ValidationException::withMessages([
                'subtasks' => 'Нельзя завершить: есть незавершённые подзадачи.',
            ]);
        }

        $task->forceFill([
            'completed'    => true,
            'completed_at' => now(),
            'progress'     => 100, // на всякий случай зафиксируем
        ])->save();

        return response()->json([
            'message' => 'Задача завершена.',
            'task' => $task->fresh()->load([
            'creator:id,name',
            'executors:id,name',
            'responsibles:id,name',
            'project.managers:id,name',
            'project.company:id,name',
            'files:id,task_id,file_path',
            'subtasks:id,task_id,title,completed',
        ]),

        ]);
    }

   public function update(Request $request, Task $task)
{
    $this->authorize('update', $task);

    $data = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'start_date' => 'nullable|date',
        'due_date' => 'nullable|date|after_or_equal:start_date',
    ]);

    $task->update($data);

    return response()->json([
        'message' => 'Задача обновлена',
        'task' => $task,
    ]);
}

public function addWatcher(Request $request, Task $task)
{
    $this->authorize('update', $task);

    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
    ]);

    $task->watcherstask()->syncWithoutDetaching([$validated['user_id']]);

    SendAssignedNotifications::dispatch($task, [$validated['user_id']], 'watcher');

    return response()->json([
        'message' => 'Наблюдатель добавлен',
        'watcherstask' => $task->watcherstask()->get(['id', 'name']),
    ]);
}


public function destroy(\App\Models\Task $task)
{
    $this->authorize('delete', $task);

    // Удаляем файлы задачи
    foreach ($task->files as $file) {
        if (\Storage::disk('public')->exists($file->file_path)) {
            \Storage::disk('public')->delete($file->file_path);
        }
        $file->delete();
    }

    // Удаляем подзадачи
    foreach ($task->subtasks as $subtask) {
        $subtask->delete();
    }

    $task->delete();

    return response()->json(['message' => 'Задача и все связанные данные удалены.']);
}






// 🔹 Изменить исполнителя (точечная замена)
public function updateExecutor(Request $request, \App\Models\Task $task)
{
    $this->authorize('manageMembers', $task);

    $data = $request->validate([
        'user_id' => 'required|exists:users,id', // новый исполнитель
        'replace_user_id' => 'nullable|exists:users,id', // кого заменяем
    ]);

    // Проверяем: не добавляем дубликата
    if ($task->executors()->where('user_id', $data['user_id'])->exists()) {
        return response()->json([
            'message' => 'Этот пользователь уже является исполнителем.',
        ], 422);
    }

    // Если есть replace_user_id — удаляем только его
    if (!empty($data['replace_user_id'])) {
        $task->executors()->detach($data['replace_user_id']);
    }

    // Добавляем нового, не трогая остальных
    $task->executors()->syncWithoutDetaching([$data['user_id']]);

    return response()->json([
        'message' => 'Исполнитель успешно изменён.',
        'executors' => $task->executors()->select('users.id', 'users.name')->get(),
    ]);
}






// 🔹 Изменить ответственного (точечная замена)
public function updateResponsible(Request $request, \App\Models\Task $task)
{
    $this->authorize('manageMembers', $task);

    $data = $request->validate([
        'user_id' => 'required|exists:users,id', // новый ответственный
        'replace_user_id' => 'nullable|exists:users,id', // кого заменяем
    ]);

    // Проверяем: не добавляем дубликата
    if ($task->responsibles()->where('user_id', $data['user_id'])->exists()) {
        return response()->json([
            'message' => 'Этот пользователь уже является ответственным.',
        ], 422);
    }

    // Если есть replace_user_id — удаляем только его
    if (!empty($data['replace_user_id'])) {
        $task->responsibles()->detach($data['replace_user_id']);
    }

    // Добавляем нового, не трогая остальных
    $task->responsibles()->syncWithoutDetaching([$data['user_id']]);

    return response()->json([
        'message' => 'Ответственный успешно изменён.',
        'responsibles' => $task->responsibles()->select('users.id', 'users.name')->get(),
    ]);
}



public function addExecutors(Request $request, Task $task)
{
    $this->authorize('manageMembers', $task);

    $validated = $request->validate([
        'user_ids' => 'required|array|min:1',
        'user_ids.*' => 'exists:users,id',
    ]);

    // ✅ добавляем, не заменяя существующих
    $task->executors()->syncWithoutDetaching($validated['user_ids']);

    SendAssignedNotifications::dispatch($task, $validated['user_ids'], 'executor');

    return response()->json([
        'message' => 'Исполнители добавлены',
        'executors' => $task->executors()->select('users.id', 'users.name')->get(),
    ]);
}

// ✅ Добавить одного или нескольких ответственных
public function addResponsibles(Request $request, Task $task)
{
    $this->authorize('manageMembers', $task);

    $validated = $request->validate([
        'user_ids' => 'required|array|min:1',
        'user_ids.*' => 'exists:users,id',
    ]);

    // ✅ добавляем, не заменяя существующих
    $task->responsibles()->syncWithoutDetaching($validated['user_ids']);

    SendAssignedNotifications::dispatch($task, $validated['user_ids'], 'responsible');

    return response()->json([
        'message' => 'Ответственные добавлены',
        'responsibles' => $task->responsibles()->select('users.id', 'users.name')->get(),
    ]);
}

public function removeExecutor(Task $task, Request $request)
{
    $this->authorize('manageMembers', $task);

    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
    ]);

    // Проверяем, что после удаления останется хотя бы один исполнитель
    $currentExecutorsCount = $task->executors()->count();
    if ($currentExecutorsCount <= 1) {
        throw ValidationException::withMessages([
            'executor' => 'Нельзя удалить всех исполнителей. В задаче должен быть хотя бы один.',
        ]);
    }

    $userId = $validated['user_id'];

    $task->executors()->detach($validated['user_id']);

    SendRemovedNotifications::dispatch($task, $userId, 'executor');

    return response()->json([
        'message' => 'Исполнитель удалён',
        'executors' => $task->executors()->select('users.id', 'users.name')->get(),
    ]);
}


// ✅ Удалить ответственного
public function removeResponsible(Task $task, Request $request)
{
    $this->authorize('manageMembers', $task);

    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
    ]);

    $currentResponsiblesCount = $task->responsibles()->count();
    if ($currentResponsiblesCount <= 1) {
        throw ValidationException::withMessages([
            'responsible' => 'Нельзя удалить всех ответственных. В задаче должен быть хотя бы один.',
        ]);
    }

    $userId = $validated['user_id'];

    $task->responsibles()->detach($validated['user_id']);

    SendRemovedNotifications::dispatch($task, $userId, 'responsible');

    return response()->json([
        'message' => 'Ответственный удалён',
        'responsibles' => $task->responsibles()->select('users.id', 'users.name')->get(),
    ]);
}


// ✅ Удалить наблюдателя
public function removeWatcher(Task $task, Request $request)
{
    $this->authorize('update', $task);

    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
    ]);

    $userId = $validated['user_id'];

    $task->watcherstask()->detach($validated['user_id']);

    SendRemovedNotifications::dispatch($task, $userId, 'watcher');

    return response()->json([
        'message' => 'Наблюдатель удалён',
        'watcherstask' => $task->watcherstask()->select('users.id', 'users.name')->get(),
    ]);
}


public function withSubtasks()
{
    // Загружаем задачи текущего пользователя
    $tasks = \App\Models\Task::where('creator_id', auth()->id())
        ->select('id', 'title')
        ->get();

    // Готовим таблицу подзадач
    $subtasks = [];

    foreach ($tasks as $task) {
        $subtasks[$task->id] = \App\Models\Subtask::where('task_id', $task->id)
            ->select('id', 'title')
            ->get();
    }

    return response()->json([
        'tasks' => $tasks,
        'subtasks' => $subtasks,
    ]);
}

    public function startWork(Request $request, Task $task)
    {
        $user = $request->user();

        // Проверка: пользователь должен быть либо исполнителем, либо ответственным
        $isExecutor = $task->executors()->where('user_id', $user->id)->exists();
        $isResponsible = $task->responsibles()->where('user_id', $user->id)->exists();

        abort_unless($isExecutor || $isResponsible, 403, 'Вы не являетесь участником этой задачи.');

        // Проверка текущего статуса
        if ($task->status === 'in_work') {
            return response()->json(['message' => 'Задача уже в работе.'], 422);
        }

        // Если пользователь ответственный, но не исполнитель - добавляем его в исполнители
        if ($isResponsible && !$isExecutor) {
            $task->executors()->attach($user->id, ['assigned_at' => now()]);
        }

        $task->update([
            'status' => 'in_work',
            'started_at' => now(), // рекомендую добавить это поле в БД
            'started_by' => $user->id // кто начал выполнение
        ]);

        // УВЕДОМЛЕНИЕ ВСЕМ УЧАСТНИКАМ (кроме того, кто взял в работу)
        $participants = $task->executors
            ->merge($task->responsibles)
            ->unique('id');

        $taskUrl = url("/tasks/{$task->id}");

        foreach ($participants as $participant) {
            if ($participant->telegram_chat_id && $participant->id !== $user->id) {
                \App\Services\TelegramService::sendMessage(
                    $participant->telegram_chat_id,
                    "🚀 <b>Задача взята в работу!</b>\n" .
                    "Задача: <b>{$task->title}</b>\n" .
                    "Взял(а): {$user->name}\n" .
                    "Роль: " . ($isExecutor ? "Исполнитель" : "Ответственный") . "\n" .
                    "🔗 <a href=\"{$taskUrl}\">Перейти к задаче</a>"
                );
            }
        }

        return response()->json([
            'message' => 'Статус задачи изменен на "В работе"',
            'task' => $task->fresh(['executors', 'responsibles']),
        ]);
    }

    // Одобрить файл
    public function approve(Request $request, TaskFile $file)
    {
        // 1. Проверяем права: только "Ответственные" (responsibles) могут согласовывать
        $this->checkReviewerPermissions($file);

        $file->update([
            'status' => 'approved',
            'rejection_reason' => null // Очищаем причину отказа, если была
        ]);

        return response()->json(['message' => 'Документ согласован', 'file' => $file]);
    }

    // Отправить на доработку
    public function reject(Request $request, TaskFile $file)
    {
        $this->checkReviewerPermissions($file);

        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        // 🔥 Создаем комментарий вместо обновления rejection_reason
        $comment = FileComment::create([
            'task_file_id' => $file->id,
            'user_id' => Auth::id(),
            'comment' => $request->comment,
            'type' => 'rejection'
        ]);

        // Обновляем статус файла
        $file->update([
            'status' => 'rejected'
        ]);

        return response()->json([
            'message' => 'Документ отправлен на доработку',
            'file' => $file->load('comments.user'),
            'comment' => $comment->load('user')
        ]);
    }

    /**
     * Добавить комментарий к файлу (для всех пользователей)
     */
    public function addComment(Request $request, TaskFile $file)
    {
        $request->validate([
            'comment' => 'required|string|max:1000',
            'type' => 'nullable|in:rejection,feedback,note'
        ]);

        // Проверяем, может ли пользователь комментировать
        // (например, если он связан с задачей)
        $task = $file->task;
        $user = Auth::user();

        // Проверяем, является ли пользователь участником задачи
        $isParticipant = $task->executors()->where('user_id', $user->id)->exists() ||
            $task->responsibles()->where('user_id', $user->id)->exists() ||
            $task->creator_id === $user->id;

        if (!$isParticipant) {
            return response()->json([
                'message' => 'Вы не можете комментировать этот файл'
            ], 403);
        }

        $comment = FileComment::create([
            'task_file_id' => $file->id,
            'user_id' => $user->id,
            'comment' => $request->comment,
            'type' => $request->type ?? 'feedback'
        ]);

        // Если файл был на доработке и его статус 'rejected',
        // можно автоматически менять статус при новом комментарии?
        // Но лучше оставить как есть

        return response()->json([
            'message' => 'Комментарий добавлен',
            'comment' => $comment->load('user')
        ]);
    }

    /**
     * Удалить комментарий (только свой)
     */
    public function deleteComment($commentId)
    {
        $comment = FileComment::findOrFail($commentId);
        $user = Auth::user();

        if ($comment->user_id !== $user->id) {
            return response()->json([
                'message' => 'Вы можете удалить только свои комментарии'
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Комментарий удален'
        ]);
    }

    /**
     * Обновить комментарий (только свой)
     */
    public function updateComment(Request $request, $commentId)
    {
        $comment = FileComment::findOrFail($commentId);
        $user = Auth::user();

        if ($comment->user_id !== $user->id) {
            return response()->json([
                'message' => 'Вы можете редактировать только свои комментарии'
            ], 403);
        }

        $request->validate([
            'comment' => 'required|string|max:1000'
        ]);

        $comment->update([
            'comment' => $request->comment
        ]);

        return response()->json([
            'message' => 'Комментарий обновлен',
            'comment' => $comment->load('user')
        ]);
    }

    /**
     * Получить все комментарии к файлу
     */
    public function getComments(TaskFile $file)
    {
        $comments = $file->comments()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($comments);
    }

    // Вспомогательный метод проверки прав
    private function checkReviewerPermissions(TaskFile $file)
    {
        $user = auth()->user();
        $task = $file->task;

        // Проверяем, есть ли текущий юзер в списке ответственных задачи
        $isResponsible = $task->responsibles()->where('users.id', $user->id)->exists();

        // 🔥 Проверяем, есть ли текущий юзер в списке исполнителей задачи
        $isExecutor = $task->executors()->where('users.id', $user->id)->exists();

        // 🔥 Проверяем, является ли пользователь создателем задачи
        $isCreator = $task->creator_id === $user->id;

        // Разрешаем доступ если пользователь: ответственный, исполнитель или создатель
        if (!$isResponsible && !$isExecutor && !$isCreator) {
            abort(403, 'Только ответственные, исполнители или создатель задачи могут согласовывать документы.');
        }
    }

    public function replace(Request $request, TaskFile $file)
    {
        // Проверка прав: менять может только тот, кто загрузил, или исполнитель задачи
        $user = auth()->user();

        // Проверяем, что пользователь может заменить файл
        $canReplace = ($file->user_id === $user->id);

        // Если не автор, проверяем, является ли исполнителем задачи
        if (!$canReplace) {
            $task = $file->task;
            $isExecutor = $task->executors()->where('user_id', $user->id)->exists();
            $isResponsible = $task->responsibles()->where('user_id', $user->id)->exists();
            $isCreator = $task->creator_id === $user->id;
            $canReplace = $isExecutor || $isResponsible || $isCreator;
        }

        if (!$canReplace) {
            return response()->json([
                'message' => 'Вы не можете заменить этот файл'
            ], 403);
        }

        $request->validate([
            'file' => 'required|file|max:20480', // до 20МБ
        ]);

        // 1. Удаляем старый файл с диска
        if (Storage::disk('public')->exists($file->file_path)) {
            Storage::disk('public')->delete($file->file_path);
        }

        // 2. Загружаем новый
        $newFile = $request->file('file');
        $originalName = $newFile->getClientOriginalName();
        $path = $newFile->store('task_files', 'public');

        // 3. Обновляем запись в БД
        $file->update([
            'file_path' => $path,
            'file_name' => $originalName,
            'status' => 'replacement',
            'rejection_reason' => null,
        ]);

        // 🔥 4. Удаляем все комментарии к этому файлу
        // Проверяем, существует ли отношение comments
        if (method_exists($file, 'comments')) {
            $file->comments()->delete();
        }

        return response()->json([
            'message' => 'Файл обновлен. Все комментарии и замечания удалены.',
            'file' => $file->fresh()->load('comments')
        ]);
    }




    public function restore($id)
    {
        // Ищем задачу, игнорируя фильтр "не завершенные"
        $task = Task::withoutGlobalScope('not_completed')->findOrFail($id);

        // $this->authorize('update', $task);

        $task->update([
            'completed' => 0,
            'progress' => 0,
            'completed_at' => null,
            'status' => 'in_work',
        ]);

        return back()->with('success', 'Задача восстановлена из архива');
    }



}
