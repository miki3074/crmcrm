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

    foreach ($recipients as $userId) {
        $user = \App\Models\User::find($userId);
        if ($user && $user->telegram_chat_id) {
            \App\Services\TelegramService::sendMessage(
                $user->telegram_chat_id,
                "🆕 Вам назначена новая задача: <b>{$task->title}</b>\n
                Приоритет: {$task->priority}\n
                Срок: {$task->due_date}"
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
        'files:id,task_id,file_path',
         'watcherstask:id,name',
        // добавили completed
        'subtasks:id,task_id,title,creator_id,start_date,due_date,progress,completed',
        'subtasks.executors:id,name',
        'subtasks.creator:id,name',
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


// public function addFiles(Request $request, Task $task)
// {
//     $this->authorize('update', $task); // если есть политика

//     $request->validate([
//         'files.*' => 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:5120',
//     ]);

//     if ($request->hasFile('files')) {
//         foreach ($request->file('files') as $file) {
//             $path = $file->store('task_files', 'public');
//             $task->files()->create(['file_path' => $path]);
//         }
//     }

//     return response()->json(['message' => 'Файлы успешно добавлены']);
// }


public function addFiles(Request $request, Task $task)
{
    $this->authorize('addFiles', $task);

    $request->validate([
        'files.*' => 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:5120',
    ]);

    if ($request->hasFile('files')) {
        foreach ($request->file('files') as $file) {
            // Получаем оригинальное имя файла
            $originalName = $file->getClientOriginalName();

            // Сохраняем с этим именем (в public/task_files/)
            $path = $file->storeAs('task_files', $originalName, 'public');

            // Записываем и путь, и имя в БД
            $task->files()->create([
                'file_path' => $path,
                'file_name' => $originalName,
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






}
