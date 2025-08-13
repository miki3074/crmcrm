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

}
