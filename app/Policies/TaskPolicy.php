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

        $task->executors()->where('users.id', $user->id)->exists() ||

        $task->responsibles()->where('users.id', $user->id)->exists() ||

        $task->project->managers->contains('id', $user->id) ||
        $task->project->executors->contains('id', $user->id) ||

        $user->id === ($task->project->company->user_id ?? 0) ||

        // 👇 Исполнитель подзадачи
        $task->subtasks()
            ->whereHas('executors', fn($q) => $q->where('users.id', $user->id))
            ->exists() ||

        // 👇 Ответственный подзадачи
        $task->subtasks()
            ->whereHas('responsibles', fn($q) => $q->where('users.id', $user->id))
            ->exists();
}


    public function view(User $user, Task $task): bool
{
    // Админ всегда может
    // if (method_exists($user, 'hasRole') && $user->hasRole('admin')) return true;

    // Владелец компании
    if (optional($task->project->company)->user_id === $user->id) return true;

    // Менеджер или исполнитель проекта
    if ($task->project->managers->contains('id', $user->id)) return true;
    if ($task->project->executors->contains('id', $user->id)) return true;

    // Наблюдатели
    if ($task->project->watchers->contains('id', $user->id)) {
        \Log::info('Project watcher access', ['user_id' => $user->id, 'task_id' => $task->id]);
        return true;
    }


    if ($task->watcherstask->contains('id', $user->id)) return true;

    // Участие в задаче
    return $this->participates($user, $task);
}


   public function comment(User $user, Task $task): bool
{
    return
        // Автор задачи
        $user->id === $task->creator_id ||

        // Исполнитель задачи
        $task->executors()->where('users.id', $user->id)->exists() ||

        // Ответственный задачи
        $task->responsibles()->where('users.id', $user->id)->exists() ||

        // Руководители проекта
        $task->project?->managers?->contains('id', $user->id) ||

        // Исполнители проекта
        $task->project?->executors?->contains('id', $user->id) ||

        // Владелец компании
        $user->id === ($task->project?->company?->user_id ?? 0);
}


    public function deleteComment(User $user, \App\Models\TaskComment $comment): bool
    {
        $task = $comment->task;
        return $user->id === $comment->user_id ||
               $task->responsibles->contains('id', $user->id) ||
               $task->project->managers->contains('id', $user->id) ||
               $task->project->executors->contains('id', $user->id) ||
               $user->id === ($task->project->company->user_id ?? 0) ||
               $user->id === $task->creator_id;
    }

     public function create(User $user, Project $project): bool
    {
        // Владелец компании
        if ($user->id === $project->company->user_id) {
            return true;
        }

        // Руководитель проекта
        if ($project->managers->contains('id', $user->id)) {
            return true;
        }

        // Исполнитель проекта
        if ($project->executors->contains('id', $user->id)) {
            return true;
        }

        return false;
    }

  public function createSubtask(User $user, Task $task): bool
{
    return
        // Автор задачи
        $user->id === $task->creator_id ||

        // Ответственные в задаче
        $task->responsibles()->where('users.id', $user->id)->exists() ||

        // Исполнители в задаче
        $task->executors()->where('users.id', $user->id)->exists() ||

        // Руководители проекта
        $task->project->managers->contains('id', $user->id) ||

        // Исполнители проекта
        $task->project->executors->contains('id', $user->id) ||

        // Владелец компании
        $user->id === ($task->project->company->user_id ?? 0);
}


 public function update(User $user, Task $task): bool
    {
        return
            $user->id === $task->creator_id ||
            optional($task->project->company)->user_id === $user->id ||
            $task->project->managers->contains('id', $user->id) ||
            $task->project->executors->contains('id', $user->id); // 👈 добавили

    }

    public function complete(User $user, Task $task): bool
    {
        return
            // 1. Создатель самой задачи
            $user->id === $task->creator_id ||

            // 2. Исполнители задачи (Executors на уровне задачи)
            $task->executors->contains('id', $user->id) ||

            // 3. Ответственные задачи (Responsibles на уровне задачи)
            $task->responsibles->contains('id', $user->id) ||

            // 4. Владелец компании, которой принадлежит проект
            optional($task->project->company)->user_id === $user->id ||

            // 5. Менеджеры проекта
            $task->project->managers->contains('id', $user->id) ||

            // 6. Исполнители проекта
            $task->project->executors->contains('id', $user->id);
    }

    public function list(User $user, Task $task): bool
    {
        return
            // 1. Создатель самой задачи
            $user->id === $task->creator_id ||

            // 2. Исполнители задачи (Executors на уровне задачи)
            $task->executors->contains('id', $user->id) ||

            // 3. Ответственные задачи (Responsibles на уровне задачи)
            $task->responsibles->contains('id', $user->id) ||

            // 4. Владелец компании, которой принадлежит проект
            optional($task->project->company)->user_id === $user->id ||

            // 5. Менеджеры проекта
            $task->project->managers->contains('id', $user->id) ||

            // 6. Исполнители проекта
            $task->project->executors->contains('id', $user->id);
    }

 public function updateProgress(User $user, Task $task): bool
    {
        return
            optional($task->project->company)->user_id === $user->id ||
            $task->project->managers->contains('id', $user->id) ||
            $task->project->executors->contains('id', $user->id) || // 👈 добавили
            $task->executors->contains('id', $user->id) ||
            $user->id === $task->creator_id ||
            $task->responsibles->contains('id', $user->id);
    }



    public function addFiles(User $user, Task $task): bool
    {
        return
            $task->executors->contains('id', $user->id) ||
            $task->responsibles->contains('id', $user->id) ||
              $task->project->executors->contains('id', $user->id) ||
            $user->id === ($task->project->company->user_id ?? 0);
    }

    public function delete(User $user, Task $task): bool
    {
        return
               $task->responsibles->contains('id', $user->id) ||
               $task->project->managers->contains('id', $user->id) ||
               $task->project->executors->contains('id', $user->id) ||
               $user->id === ($task->project->company->user_id ?? 0) ||
               $user->id === $task->creator_id;
    }

    public function deletetask(User $user, \App\Models\Task $task): bool
{

    return

        $user->id === $task->project->company->user_id ||
        $task->project->managers->contains('id', $user->id) ||
            $task->project->executors->contains('id', $user->id);
}

public function manageMembers(User $user, \App\Models\Task $task): bool
{
    // Разрешено владельцу компании и менеджеру проекта
    return
        $user->id === $task->project->company->user_id ||
        $task->project->managers->contains('id', $user->id) ||
            $task->project->executors->contains('id', $user->id);
}


public function deleteFile(User $user, Task $task): bool
{
    return
        $user->id === $task->creator_id || // автор задачи
        $task->executors()->where('users.id', $user->id)->exists() ||
        $task->responsibles()->where('users.id', $user->id)->exists() ||
        $task->project?->managers?->contains('id', $user->id) ||
        $task->project?->executors?->contains('id', $user->id) ||
        $user->id === ($task->project?->company?->user_id ?? 0);
}



}
