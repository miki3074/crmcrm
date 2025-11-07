<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Просмотр списка проектов
     */
    public function viewAny(User $user): bool
    {
        // например, все аутентифицированные могут видеть список
        return $user !== null;
    }

    /**
     * Просмотр конкретного проекта
     */
    public function view(User $user, Project $project): bool
    {
        // Владелец компании или инициатор
        if ($user->id === $project->company->user_id || $user->id === $project->initiator_id) {
            return true;
        }

        // Один из руководителей
        if ($project->managers->contains('id', $user->id)) {
            return true;
        }

        if ($project->executors->contains('id', $user->id)) {
        return true;
    }

        // Исполнитель хотя бы одной задачи
if ($project->tasks()
    ->whereHas('executors', fn($q) => $q->where('users.id', $user->id))
    ->exists()) {
    return true;
}

// Ответственный хотя бы одной задачи
if ($project->tasks()
    ->whereHas('responsibles', fn($q) => $q->where('users.id', $user->id))
    ->exists()) {
    return true;
}

// Исполнитель подзадачи
if (\App\Models\Subtask::whereHas('task', fn($q) => $q->where('project_id', $project->id))
    ->whereHas('executors', fn($q) => $q->where('users.id', $user->id))
    ->exists()) {
    return true;
}

// Ответственный подзадачи
if (\App\Models\Subtask::whereHas('task', fn($q) => $q->where('project_id', $project->id))
    ->whereHas('responsibles', fn($q) => $q->where('users.id', $user->id))
    ->exists()) {
    return true;
}

        // 👁 Наблюдатель проекта
    if ($project->watchers->contains('id', $user->id)) {
        return true;
    }

        return false;
    }

    /**
     * Создание проекта
     */
    public function create(User $user): bool
    {
        // только владелец компании (или админ, если нужно)
        return true;
    }

    /**
     * Создание задач внутри проекта
     */
    public function createTask(User $user, Project $project): bool
    {
        return
            $user->id === $project->company->user_id ||
            $project->managers->contains('id', $user->id) ||
            $project->executors->contains('id', $user->id); // 🆕 добавили исполнителей
    }

    /**
     * Обновление проекта
     */
    public function update(User $user, Project $project): bool
    {
        return $project->managers->contains('id', $user->id)
            || $project->managers->contains('id', $user->id) ||
            $project->executors->contains('id', $user->id); // 🆕 добавили исполнителей
    }

     public function updateman(User $user, Project $project): bool
    {
        return $user->id === $project->company->user_id;
    }
    /**
     * Обновление бюджета
     */
    public function updateBudget(User $user, Project $project): bool
    {
        // только владелец компании
        return $user->id === $project->company->user_id;
    }

    /**
     * Обновление описания
     */
    public function updateDescription(User $user, Project $project): bool
    {
        return
            $user->id === $project->company->user_id ||
            $project->managers->contains('id', $user->id) ||
            $project->executors->contains('id', $user->id); // 🆕 добавили исполнителей
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->id === $project->company->user_id;
    }

    public function deletepr(User $user, Project $project): bool
{
    // Только владелец компании или администратор
    return $user->id === $project->company->user_id || ($user->hasRole('admin') ?? false);
}

public function updatewat(User $user, Project $project): bool
{
   return
            $user->id === $project->company->user_id ||
            $project->managers->contains('id', $user->id) ||
            $project->executors->contains('id', $user->id); // 🆕 добавили исполнителей
}



    public function restore(User $user, Project $project): bool
    {
        return false;
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return false;
    }
}
