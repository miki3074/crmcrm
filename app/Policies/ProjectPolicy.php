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
        $userId = $user->id;

        // 1️⃣ Владелец компании
        if ($userId === $project->company->user_id) {
            return true;
        }

        // 2️⃣ Инициатор проекта
        if ($userId === $project->initiator_id) {
            return true;
        }

        // 3️⃣ Менеджер проекта
        if ($project->managers->contains('id', $userId)) {
            return true;
        }

        // 4️⃣ Исполнитель проекта
        if ($project->executors->contains('id', $userId)) {
            return true;
        }

        // 5️⃣ Наблюдатель проекта
        if ($project->watchers->contains('id', $userId)) {
            return true;
        }

        // 6️⃣ Исполнитель хотя бы одной задачи
        if (
            $project->tasks()
                ->whereHas('executors', fn($q) => $q->where('users.id', $userId))
                ->exists()
        ) {
            return true;
        }

        // 7️⃣ Ответственный хотя бы одной задачи
        if (
            $project->tasks()
                ->whereHas('responsibles', fn($q) => $q->where('users.id', $userId))
                ->exists()
        ) {
            return true;
        }

        // 8️⃣ Исполнитель подзадачи
        if (
            \App\Models\Subtask::whereHas('task', fn($q) => $q->where('project_id', $project->id))
                ->whereHas('executors', fn($q) => $q->where('users.id', $userId))
                ->exists()
        ) {
            return true;
        }

        // 9️⃣ Ответственный подзадачи
        if (
            \App\Models\Subtask::whereHas('task', fn($q) => $q->where('project_id', $project->id))
                ->whereHas('responsibles', fn($q) => $q->where('users.id', $userId))
                ->exists()
        ) {
            return true;
        }

        return false;
    }


    /**
     * Создание проекта
     */
   public function create(User $user, Company $company): bool
{
    return
        $company->user_id === $user->id ||
        $company->users()
            ->wherePivot('role', 'manager')
            ->where('users.id', $user->id)
            ->exists();
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
        return
            // владелец компании
            $user->id === $project->company->user_id

            // менеджер проекта
            || $project->managers->contains('id', $user->id)

            // исполнитель проекта
            || $project->executors->contains('id', $user->id);
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
        return
            $user->id === $project->company->user_id ||
            $user->id === $project->initiator_id;
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
