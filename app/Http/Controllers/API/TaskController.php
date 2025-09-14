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
        'executor_id' => 'required|exists:users,id',
        'responsible_id' => 'required|exists:users,id',
        'project_id' => 'nullable|exists:projects,id',
        'subproject_id' => 'nullable|exists:subprojects,id',
        'company_id' => 'nullable|exists:companies,id',
        'files.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:5120',
    ]);

    // если subproject_id передан, то подтянем project_id и company_id
    if (!empty($validated['subproject_id'])) {
        $subproject = \App\Models\Subproject::with('project.company')->findOrFail($validated['subproject_id']);
        $validated['project_id'] = $subproject->project_id;
        $validated['company_id'] = $subproject->project->company_id;
    }

    $task = Task::create([
        ...$validated,
        'creator_id' => auth()->id()
    ]);

    if ($request->hasFile('files')) {
        foreach ($request->file('files') as $file) {
            $path = $file->store('task_files', 'public');
            $task->files()->create(['file_path' => $path]);
        }
    }

    return response()->json($task->load(['executor', 'responsible']), 201);
}


public function show($id)
{
    $task = Task::with([
        'creator:id,name',
        'executor:id,name',
        'responsible:id,name',
        'project:id,name,company_id,manager_id',
        'project.company:id,name',
        'files:id,task_id,file_path',
        // добавили completed
        'subtasks:id,task_id,title,executor_id,creator_id,start_date,due_date,completed',
        'subtasks.executor:id,name',
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


public function addFiles(Request $request, Task $task)
{
    $this->authorize('update', $task); // если есть политика

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
            'task'    => $task->fresh()->load([
                'creator:id,name',
                'executor:id,name',
                'responsible:id,name',
                'project:id,name,company_id,manager_id',
                'project.company:id,name',
                'files:id,task_id,file_path',
                'subtasks:id,task_id,title,completed',
            ]),
        ]);
    }


}
