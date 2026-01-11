<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Task;
use App\Models\TaskFile;

use Illuminate\Support\Facades\Storage;

use Illuminate\Validation\ValidationException;

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
        'files.*.mimes' => 'Можно загружать только файлы PDF, Word или Excel.',
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
        'files.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:5120',
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
    $task = Task::with([
        'project.company:id,name,user_id',
        'creator:id,name',
        'executors:id,name',
        'responsibles:id,name',
        'project:id,name,company_id',
        'project.managers:id,name',
        'project.company:id,name',

        'files:id,task_id,file_path,user_id,file_name,status,rejection_reason,created_at',


        'watcherstask:id,name',
        // добавили completed
        'subtasks:id,task_id,title,creator_id,start_date,due_date,progress,completed',
        'subtasks.executors:id,name',
        'subtasks.creator:id,name',
        'producers:id,name,company_id',
        'buyers:id,name,company_id',
    ])->findOrFail($id);

    $this->authorize('view', $task);

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

    $request->validate([
        'files.*' => 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:5120',
        'requires_approval' => 'nullable|boolean', // 👈 Новое поле
    ]);

    $initialStatus = $request->boolean('requires_approval') ? 'pending' : 'none';

    if ($request->hasFile('files')) {
    foreach ($request->file('files') as $file) {
        $originalName = $file->getClientOriginalName();
        $path = $file->storeAs('task_files', $originalName, 'public');

        $task->files()->create([
            'file_path' => $path,
            'file_name' => $originalName,
            'user_id' => auth()->id(),  // 👈 сохраняем
            'status' => $initialStatus,
        ]);
    }
}


    return response()->json(['message' => 'Файлы успешно добавлены']);
}



public function downloadFile($fileId)
{
    $file = \App\Models\TaskFile::findOrFail($fileId);

    // Проверка доступа к задаче
    $this->authorize('view', $file->task);

    $path = $file->file_path;

    if (!Storage::disk('public')->exists($path)) {
        return response()->json(['message' => 'Файл не найден.'], Response::HTTP_NOT_FOUND);
    }

    $originalName = basename($path); // или сохрани оригинальное имя при загрузке

    return Storage::disk('public')->download($path, $originalName);
}


// public function deleteFile(TaskFile $file)
// {
//     $user = auth()->user();

//     // Сохраняем id заранее, иначе после delete оно будет null
//     $fileId = $file->id;

//     // Подгружаем задачу, если есть
//     $file->loadMissing('task.executors', 'task.responsibles');

//     // 🔓 Разрешаем удаление всем, даже если user_id = null
//     $canDelete = true;

//     if ($file->task) {
//         $canDelete =
//             ($file->user_id && $file->user_id === $user->id) ||
//             $file->task->executors->contains('id', $user->id) ||
//             $file->task->responsibles->contains('id', $user->id);
//     }

//     if (!$canDelete) {
//         return response()->json(['message' => 'Нет прав на удаление файла'], 403);
//     }

//     // 🗑 Удаляем сам файл, если путь есть
//     if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
//         Storage::disk('public')->delete($file->file_path);
//     }

//     // 🧹 Удаляем запись из БД в любом случае
//     $file->delete();

//     // Возвращаем корректный id
//     return response()->json([
//         'message' => 'Файл удалён',
//         'file_id' => $fileId
//     ]);
// }



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
        $this->authorize('update', $task);

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

    $task->executors()->detach($validated['user_id']);

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

    $task->responsibles()->detach($validated['user_id']);

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

    $task->watcherstask()->detach($validated['user_id']);

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
        // Проверка: брать в работу может только исполнитель или админ
        // Можно создать отдельный gate 'startWork' или использовать существующую логику
        // Обычно allow, если user существует в executors
        $user = $request->user();

        $isExecutor = $task->executors()->where('user_id', $user->id)->exists();

        // Если у вас нет отдельной политики, проверяем тут:
        abort_unless($isExecutor || $this->authorize('update', $task), 403, 'Вы не являетесь исполнителем этой задачи.');

        if ($task->status === 'in_work') {
            return response()->json(['message' => 'Задача уже в работе.'], 422);
        }

        $task->update([
            'status' => 'in_work',
            // 'started_at' => now(), // если добавите такое поле в БД, будет полезно для аналитики
        ]);

        // УВЕДОМЛЕНИЕ ОТВЕТСТВЕННЫМ (Менеджерам)
        // Собираем всех ответственных
        $responsibles = $task->responsibles;

        $taskUrl = url("/tasks/{$task->id}");

        foreach ($responsibles as $resp) {
            if ($resp->telegram_chat_id && $resp->id !== $user->id) {
                \App\Services\TelegramService::sendMessage(
                    $resp->telegram_chat_id,
                    "🚀 <b>Задача взята в работу!</b>\n".
                    "Задача: <b>{$task->title}</b>\n".
                    "Исполнитель: {$user->name}\n".
                    "🔗 <a href=\"{$taskUrl}\">Перейти к задаче</a>"
                );
            }
        }

        return response()->json([
            'message' => 'Статус задачи изменен на "В работе"',
            'task' => $task->fresh(), // возвращаем обновленную задачу
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
            'reason' => 'required|string|max:1000', // Причина обязательна
        ]);

        $file->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason
        ]);

        return response()->json(['message' => 'Документ отправлен на доработку', 'file' => $file]);
    }

    // Вспомогательный метод проверки прав
    private function checkReviewerPermissions(TaskFile $file)
    {
        $user = auth()->user();
        $task = $file->task;

        // Проверяем, есть ли текущий юзер в списке ответственных задачи
        // (Предполагается связь task -> belongsToMany -> responsibles)
        $isResponsible = $task->responsibles()->where('users.id', $user->id)->exists();

        if (!$isResponsible) {
            abort(403, 'Только ответственные за задачу могут согласовывать документы.');
        }
    }

    public function replace(Request $request, TaskFile $file)
    {
        // Проверка прав: менять может только тот, кто загрузил, или исполнитель задачи
        if (auth()->id() !== $file->user_id) {
            // Можно добавить проверку на роль исполнителя, если нужно
            // abort(403, 'Вы не можете заменить этот файл');
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
            'file_name' => $originalName, // Обновляем имя на новое
            'status' => 'pending',        // 👈 Сбрасываем статус на "На проверке"
            'rejection_reason' => null,   // Очищаем причину отказа
        ]);

        return response()->json(['message' => 'Файл обновлен', 'file' => $file]);
    }


}
