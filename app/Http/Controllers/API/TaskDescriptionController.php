<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;

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

        $task->update([
            'description' => $data['description'] ?? null,
        ]);

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
