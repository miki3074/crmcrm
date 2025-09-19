<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Task;
use App\Models\TaskFile;

class TaskController extends Controller
{
    
public function store(Request $request)
{
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
    ]);

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
            $path = $file->store('task_files', 'public');
            $task->files()->create(['file_path' => $path]);
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
        // добавили completed
        'subtasks:id,task_id,title,creator_id,start_date,due_date,completed',
        'subtasks.executors:id,name',
        'subtasks.creator:id,name',
    ])->findOrFail($id);

    $this->authorize('view', $task);
    return response()->json($task);
}


public function updateProgress(Request $request, Task $task)
{
    $this->authorize('update', $task); // если есть политика

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
            $path = $file->store('task_files', 'public');
            $task->files()->create(['file_path' => $path]);
        }
    }

    return response()->json(['message' => 'Файлы успешно добавлены']);
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


}
