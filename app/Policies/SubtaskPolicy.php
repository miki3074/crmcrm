<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Subtask;
use App\Models\User;

class SubtaskPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
//     public function view(User $user, Subtask $subtask): bool
// {
//     // ✅ Владелец компании
//     if ($user->id === $subtask->task->project->company->user_id) {
//         return true;
//     }

//     // ✅ Создатель подзадачи
//     if ($user->id === $subtask->creator_id) {
//         return true;
//     }

//     // ✅ Исполнитель подзадачи
//     if ($user->id === $subtask->executor_id) {
//         return true;
//     }

//     // ✅ Ответственный за задачу
//     if ($user->id === $subtask->task->responsible_id) {
//         return true;
//     }

//     // ✅ Руководитель проекта
//     if ($user->id === $subtask->task->project->manager_id) {
//         return true;
//     }

//     return false;
// }

public function view(User $user, Subtask $subtask): bool
    {
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) return true;

        $task = $subtask->task;
        return
            $user->id === $subtask->creator_id ||
            $user->id === $subtask->executor_id ||
            $user->id === $task->creator_id ||
            $user->id === $task->executor_id ||
            $user->id === $task->responsible_id ||
            $user->id === ($task->project->manager_id ?? 0) ||
            $user->id === optional($task->project->company)->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $task = $subtask->task;
    return $user->id === $task->responsible_id || $user->id === $task->project->manager_id;
    }

    public function updateProgress(User $user, Subtask $subtask): bool
    {
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) return true;
        return $user->id === $subtask->executor_id || $user->id === $subtask->creator_id;
    }

    public function complete(User $user, \App\Models\Subtask $subtask): bool
{
    if (method_exists($user, 'hasRole') && $user->hasRole('admin')) return true;
    return $user->id === $subtask->executor_id || $user->id === $subtask->creator_id;
}

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Subtask $subtask): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Subtask $subtask): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Subtask $subtask): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Subtask $subtask): bool
    {
        //
    }
}
