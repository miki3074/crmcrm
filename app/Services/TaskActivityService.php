<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskActivity;

class TaskActivityService
{
    /**
     * Разослать событие всем участникам задачи (исполнители, ответственные,
     * наблюдатели, создатель), кроме того, кто это событие вызвал.
     */
    public static function notifyParticipants(Task $task, string $type, string $message, ?int $actorId, array $extraExclude = []): void
    {
        $participantIds = collect()
            ->merge($task->executors()->pluck('users.id'))
            ->merge($task->responsibles()->pluck('users.id'))
            ->merge($task->watcherstask()->pluck('users.id'))
            ->push($task->creator_id)
            ->filter()
            ->unique()
            ->reject(fn ($id) => (int) $id === (int) $actorId || in_array((int) $id, $extraExclude, true))
            ->values();

        if ($participantIds->isEmpty()) {
            return;
        }

        $now = now();

        $rows = $participantIds->map(fn ($userId) => [
            'task_id' => $task->id,
            'user_id' => $userId,
            'actor_id' => $actorId,
            'type' => $type,
            'message' => $message,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        TaskActivity::insert($rows);
    }

    /**
     * Отправить событие только одному конкретному пользователю
     * (личное уведомление, а не рассылка всем участникам).
     */
    public static function notifyUser(Task $task, int $userId, string $type, string $message, ?int $actorId): void
    {
        if ($userId === $actorId) {
            return;
        }

        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => $userId,
            'actor_id' => $actorId,
            'type' => $type,
            'message' => $message,
        ]);
    }
}
