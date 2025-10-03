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
        $this->authorize('view', $task);

       $subtasks = $task->subtasks()
    ->with(['creator:id,name', 'executors:id,name', 'responsibles:id,name'])
    ->get();


        return response()->json($subtasks);
    }

public function store(Request $request, Task $task)
{
    $this->authorize('createSubtask', $task);

    $validated = $request->validate([
    'title'          => 'required|string|max:255',
    'executor_id'    => 'required|array',
    'executor_id.*'  => 'exists:users,id',
    'responsible_id' => 'required|array',
    'responsible_id.*' => 'exists:users,id',
    'start_date'     => 'required|date',
    'due_date'       => 'required|date|after_or_equal:start_date',
]);

    $subtask = $task->subtasks()->create([
        'title'      => $validated['title'],
        'start_date' => $validated['start_date'],
        'due_date'   => $validated['due_date'],
        'creator_id' => auth()->id(),
    ]);

    // привязываем
$subtask->executors()->sync($validated['executor_id']);
$subtask->responsibles()->sync($validated['responsible_id']);

     $recipients = array_unique([
        $validated['executor_id'],
        $validated['responsible_id'],
    ]);

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
    'creator:id,name',
    'executors:id,name',
    'responsibles:id,name',
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
}
