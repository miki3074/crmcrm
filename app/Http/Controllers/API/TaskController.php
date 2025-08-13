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
        'project_id' => 'required|exists:projects,id',
        'company_id' => 'required|exists:companies,id',
        'files.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx|max:5120',
    ]);

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
        'project:id,name,company_id',
        'project.company:id,name',
        'files:id,task_id,file_path',
        'subtasks.executor:id,name', 
        'subtasks.creator:id,name'   
    ])->findOrFail($id);

    $this->authorize('view', $task); // если используешь политику

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


}
