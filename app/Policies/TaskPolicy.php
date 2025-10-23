<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    // public function viewAny(User $user): bool
    // {
    //     // Админ видит все задачи
    //     if ($user->hasRole('admin')) return true;
    //     return true; // либо можно ограничить по своему
    // }

    /**
     * Проверка участия в задаче (создатель, исполнитель, ответственный,
     * менеджер проекта, владелец компании, исполнитель подзадачи)
     */
    private function participates(User $user, Task $task): bool
    {
        return
            $user->id === $task->creator_id ||
            $task->executors->contains('id', $user->id) ||
            $task->responsibles->contains('id', $user->id) ||
            $task->project->managers->contains('id', $user->id) ||
            $user->id === ($task->project->company->user_id ?? 0) ||
            $task->subtasks()->whereHas('executors', fn($q) => $q->where('users.id', $user->id))->exists();
    }

    public function view(User $user, Task $task): bool
    {
        // if ($user->hasRole('admin')) return true;
        if (optional($task->project->company)->user_id === $user->id) return true;

        // 👁 Наблюдатель проекта
    if ($task->project->watchers->contains('id', $user->id)) {
        return true;
    }

    // 👁 Наблюдатель задачи
if ($task->watcherstask->contains('id', $user->id)) {
    return true;
}


        return $this->participates($user, $task);
    }

    public function comment(User $user, Task $task): bool
    {
        return $this->participates($user, $task);
    }

    public function deleteComment(User $user, \App\Models\TaskComment $comment): bool
    {
        $task = $comment->task;
        return $user->id === $comment->user_id ||
               $task->responsibles->contains('id', $user->id) ||
               $task->project->managers->contains('id', $user->id) ||
               $user->id === ($task->project->company->user_id ?? 0) ||
               $user->id === $task->creator_id;
    }

    public function create(User $user): bool
    {
        // Можно, например, разрешить менеджерам и владельцу компании
        return $user->hasRole('admin');
    }

    public function createSubtask(User $user, Task $task): bool
    {
        return $task->responsibles->contains('id', $user->id) ||
               $task->project->managers->contains('id', $user->id);
    }

    public function update(User $user, Task $task): bool
{
    // Админ всегда может
    // if ($user->hasRole('admin')) return true;

    // Создатель задачи
    if ($user->id === $task->creator_id) return true;

    // Владелец компании
    if (optional($task->project->company)->user_id === $user->id) return true;

    // Руководитель проекта
    if ($task->project->managers->contains('id', $user->id)) return true;

    return false;
}

public function updateProgress(User $user, Task $task): bool
{
   

    // Владелец компании
    if (optional($task->project->company)->user_id === $user->id) return true;

    // Руководитель проекта
    if ($task->project->managers->contains('id', $user->id)) return true;

    // Исполнители тоже могут менять прогресс
    if ($task->executors->contains('id', $user->id)) return true;

    if ($task->responsibles->contains('id', $user->id)) return true;

    return false;
}



    public function addFiles(User $user, Task $task): bool
    {
        return 
            $task->executors->contains('id', $user->id) ||
            $task->responsibles->contains('id', $user->id) ||
            $user->id === ($task->project->company->user_id ?? 0);
    }

    public function delete(User $user, Task $task): bool
    {
        return
               $task->responsibles->contains('id', $user->id) ||
               $task->project->managers->contains('id', $user->id) ||
               $user->id === ($task->project->company->user_id ?? 0) ||
               $user->id === $task->creator_id;
    }

    public function deletetask(User $user, \App\Models\Task $task): bool
{

    return
       
        $user->id === $task->project->company->user_id ||
        $task->project->managers->contains('id', $user->id);
}

public function manageMembers(User $user, \App\Models\Task $task): bool
{
    // Разрешено владельцу компании и менеджеру проекта
    return
        $user->id === $task->project->company->user_id ||
        $task->project->managers->contains('id', $user->id);
}


}
