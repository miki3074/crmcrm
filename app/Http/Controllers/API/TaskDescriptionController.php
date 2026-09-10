<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Services\TaskActivityService;

class TaskDescriptionController extends Controller
{
    /**
     * Добавление или обновление описания задачи.
     */
    public function update(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $data = $request->validate([
            'description' => 'nullable|string|max:5000',
        ]);

        $newDescription = $data['description'] ?? null;
        $changed = $newDescription !== $task->description;

        $task->update([
            'description' => $newDescription,
        ]);

        if ($changed) {
            TaskActivityService::notifyParticipants($task, 'description_changed', 'Изменено описание задачи', $request->user()->id);
        }

        return response()->json([
            'message' => 'Описание задачи обновлено успешно.',
            'description' => $task->description,
        ]);
    }

    /**
     * Получить текущее описание.
     */
    public function show(Task $task)
    {
        $this->authorize('view', $task);

        return response()->json([
            'description' => $task->description,
        ]);
    }
}
