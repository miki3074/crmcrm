<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
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
public function view(User $user, Project $project): bool
{
    // Владелец проекта (инициатор) или руководитель
    if ($user->id === $project->initiator_id || $user->id === $project->manager_id) {
        return true;
    }

    // Исполнитель хотя бы одной задачи
    if ($project->tasks()->where('executor_id', $user->id)->exists()) {
        return true;
    }

    // Ответственный хотя бы одной задачи
    if ($project->tasks()->where('responsible_id', $user->id)->exists()) {
        return true;
    }

    // ✅ Исполнитель хотя бы одной подзадачи в проекте
    if (\App\Models\Subtask::whereHas('task', function ($query) use ($project) {
        $query->where('project_id', $project->id);
    })->where('executor_id', $user->id)->exists()) {
        return true;
    }

    return false;
}



    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
   public function update(User $user, Project $project): bool
    {
        return $user->id === $project->manager_id
            || $user->id === $project->company->user_id
            || $user->hasRole('admin');
    }

    public function updateBudget(User $user, Project $project): bool
{
    // только владелец компании
    return $user->id === $project->company->user_id || $user->hasRole('admin');
}

public function updateDescription(User $user, Project $project): bool
{
    // руководитель проекта или владелец компании (и админ, если нужно)
    return $user->id === $project->manager_id
        || $user->id === $project->company->user_id
        || $user->hasRole('admin');
}

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Project $project): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Project $project): bool
    {
        //
    }
}
