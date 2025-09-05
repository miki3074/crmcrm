<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Админ видит список всех задач
        if ($user->hasRole('admin')) return true;

        // Иначе — ограничь по своему усмотрению (например, есть хотя бы одна доступная задача)
        return true;
    }

private function participates(User $user, Task $task): bool
{
    return
        $user->id === $task->creator_id ||
        $user->id === $task->executor_id ||
        $user->id === $task->responsible_id ||
        $user->id === ($task->project->manager_id ?? 0) ||
        $user->id === ($task->project->company->user_id ?? 0) ||
        $task->subtasks()->where('executor_id', $user->id)->exists();
}



    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Task $task): bool
    {
        // 0) Админ видит всё
        if ($user->hasRole('admin')) return true;

        // 1) Владелец компании видит задачи своей компании
        if (optional($task->project->company)->user_id === $user->id) return true;

        // 2) Базовые правила
        if (
            $user->id === $task->creator_id ||
            $user->id === $task->executor_id ||
            $user->id === $task->responsible_id ||
            $user->id === ($task->project->manager_id ?? 0)
        ) {
            return true;
        }

        return $this->participates($user, $task);

        // 3) Исполнитель любой подзадачи этой задачи тоже может просматривать
        return $task->subtasks()->where('executor_id', $user->id)->exists();
    }

    public function comment(User $user, Task $task): bool
{
    // тем же правилам, что и view
    return $this->participates($user, $task);
}

public function deleteComment(User $user, \App\Models\TaskComment $comment): bool
{
    $task = $comment->task;
    // удалить может автор комментария ИЛИ ответственный/менеджер/владелец/создатель
    return $user->id === $comment->user_id ||
           $user->id === $task->responsible_id ||
           $user->id === ($task->project->manager_id ?? 0) ||
           $user->id === ($task->project->company->user_id ?? 0) ||
           $user->id === $task->creator_id;
}

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
    }

    public function createSubtask(User $user, Task $task): bool
{
    return $user->id === $task->responsible_id || $user->id === $task->project->manager_id;
}

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Task $task): bool
    {
       return $user->id === $task->executor_id || $user->id === $task->responsible_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Task $task): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Task $task): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Task $task): bool
    {
        //
    }
}
