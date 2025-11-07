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
    $task = $subtask->task;
    $project = $task?->project;
    $companyUserId = $project?->company?->user_id;

    return
        // 🔹 Админ
        // (method_exists($user, 'hasRole') && $user->hasRole('admin')) ||

        // 🔹 Владелец компании
        $user->id === $companyUserId ||

        // 🔹 Автор подзадачи
        $user->id === $subtask->creator_id ||

        // 🔹 Исполнители / ответственные подзадачи
        $subtask->executors->contains('id', $user->id) ||
        $subtask->responsibles->contains('id', $user->id) ||

        // 🔹 Исполнители / ответственные задачи
        ($task && $task->executors->contains('id', $user->id)) ||
        ($task && $task->responsibles->contains('id', $user->id)) ||

        // 🔹 Менеджеры / исполнители проекта
        ($project && $project->managers->contains('id', $user->id)) ||
        ($project && $project->executors->contains('id', $user->id));
}


public function addFiles(User $user, Subtask $subtask): bool
{
    $project = $subtask->task->project;

    return
        $user->id === $subtask->creator_id || // автор подзадачи
        $subtask->executors->contains('id', $user->id) || // исполнитель
        $subtask->responsibles->contains('id', $user->id) || // ответственный
        $project->executors->contains('id', $user->id) || // исполнитель проекта
        $project->managers->contains('id', $user->id) || // руководитель проекта
        $user->id === optional($project->company)->user_id; // владелец компании
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
   

    $project = $subtask->task->project;

    return
        // Автор подзадачи
        $user->id === $subtask->creator_id ||

        // Исполнитель подзадачи
        $subtask->executors->contains('id', $user->id) ||

        // Ответственный подзадачи
        $subtask->responsibles->contains('id', $user->id) ||

        // Исполнитель проекта
        $project->executors->contains('id', $user->id) ||

        // Руководитель проекта
        $project->managers->contains('id', $user->id) ||

        // Владелец компании
        $user->id === optional($project->company)->user_id;
}

 public function complete(User $user, Subtask $subtask): bool
{
    

    $project = $subtask->task->project;

    return
        $user->id === $subtask->creator_id ||
        $subtask->executors->contains('id', $user->id) ||
        $subtask->responsibles->contains('id', $user->id) ||
        $project->managers->contains('id', $user->id) ||
        $project->executors->contains('id', $user->id) ||
        $user->id === optional($project->company)->user_id;
}


    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Subtask $subtask): bool
{
    $project = $subtask->task->project;

    return
        // 🔹 Автор подзадачи
        $user->id === $subtask->creator_id ||

        // 🔹 Владелец компании
        $user->id === optional($project->company)->user_id ||

        // 🔹 Руководитель проекта
        $project->managers->contains('id', $user->id) ||

        // 🔹 Исполнитель проекта
        $project->executors->contains('id', $user->id);
}


    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Subtask $subtask): bool
    {
        // Владелец компании
        if (optional($subtask->task->project->company)->user_id === $user->id) {
            return true;
        }

        // Менеджер проекта
        if ($subtask->task->project->managers->contains('id', $user->id)) {
            return true;
        }

        // Автор подзадачи
        if ($subtask->creator_id === $user->id) {
            return true;
        }

        return false;
    }

public function manageMembers(User $user, Subtask $subtask): bool
{
    return
        $user->id === $subtask->task->project->company->user_id ||
        $subtask->task->project->managers->contains('id', $user->id) ||
        $subtask->task->project->executors->contains('id', $user->id);
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


public function createSubtask(User $user, \App\Models\Subtask $subtask): bool
{
    $project = $subtask->task->project;

    // 🔹 Админ
    if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
        return true;
    }

    // 🔹 Владелец компании
    if ($project->company && $project->company->user_id === $user->id) {
        return true;
    }

    // 🔹 Менеджеры проекта
    if ($project->managers && $project->managers->contains('id', $user->id)) {
        return true;
    }

    // 🔹 Исполнители проекта
    if ($project->executors && $project->executors->contains('id', $user->id)) {
        return true;
    }

    // 🔹 Ответственные и исполнители подзадачи
    if ($subtask->executors->contains('id', $user->id)) {
        return true;
    }

    if ($subtask->responsibles->contains('id', $user->id)) {
        return true;
    }

    // 🔹 Автор подзадачи
    if ($subtask->creator_id === $user->id) {
        return true;
    }

    return false;
}




}
