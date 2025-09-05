<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subtask;
use App\Models\Task;

class SubtaskController extends Controller
{


public function index(Task $task)
{
    $this->authorize('view', $task); // Убедись, что пользователь может просматривать задачу

    $subtasks = $task->subtasks()->with(['creator:id,name', 'executor:id,name'])->get();

    return response()->json($subtasks);
}

    public function store(Request $request, Task $task)
    {
        $this->authorize('createSubtask', $task); // если используешь политику

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'executor_id' => 'required|exists:users,id',
            'start_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:start_date',
        ]);

        $subtask = $task->subtasks()->create([
            ...$validated,
            'creator_id' => auth()->id(),
        ]);

        return response()->json($subtask, 201);
    }

   public function show(Subtask $subtask)
{
    $subtask->load([
        'task.project.company',
        'creator:id,name',
        'executor:id,name'
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

    return response()->json(['message' => 'Прогресс обновлён', 'progress' => $subtask->progress]);
}

public function complete(Request $request, \App\Models\Subtask $subtask)
{
    $this->authorize('complete', $subtask);

    if ((int)$subtask->progress < 100) {
        return response()->json([
            'message' => 'Подзадачу можно завершить только при прогрессе 100%.'
        ], 422);
    }

    if ($subtask->completed) {
        return response()->json([
            'message' => 'Подзадача уже завершена.',
            'completed' => true,
            'completed_at' => $subtask->completed_at,
        ]);
    }

    $subtask->forceFill([
        'completed' => true,
        'completed_at' => now(),
    ])->save();

    return response()->json([
        'message' => 'Подзадача завершена.',
        'completed' => true,
        'completed_at' => $subtask->completed_at,
    ]);
}

}
