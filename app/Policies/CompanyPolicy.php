<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\Company;
use App\Models\User;

class CompanyPolicy
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
public function view(User $user, Company $company): bool
{
    // Владелец компании
    if ($company->user_id === $user->id) {
        return true;
    }

    

    // Менеджер хотя бы одного проекта в этой компании
    if ($company->projects()->whereHas('managers', fn($q) => $q->where('users.id', $user->id))->exists()) {
    return true;
}

if ($company->projects()
        ->whereHas('executors', fn($q) => $q->where('users.id', $user->id))
        ->exists()) {
        return true;
    }


    // Исполнитель хотя бы одной задачи
    if (\App\Models\Task::whereIn('project_id', $company->projects()->pluck('id'))
    ->whereHas('executors', fn($q) => $q->where('users.id', $user->id))
    ->exists()) {
    return true;
}

    // ✅ Ответственный хотя бы одной задачи
    if (\App\Models\Task::whereIn('project_id', $company->projects()->pluck('id'))
    ->whereHas('responsibles', fn($q) => $q->where('users.id', $user->id))
    ->exists()) {
    return true;
}

   // Исполнитель подзадачи
if (\App\Models\Subtask::whereHas('task', function ($query) use ($company) {
        $query->whereIn('project_id', $company->projects()->pluck('id'));
    })
    ->whereHas('executors', fn($q) => $q->where('users.id', $user->id))
    ->exists()
) {
    return true;
}

// Ответственный подзадачи
if (\App\Models\Subtask::whereHas('task', function ($query) use ($company) {
        $query->whereIn('project_id', $company->projects()->pluck('id'));
    })
    ->whereHas('responsibles', fn($q) => $q->where('users.id', $user->id))
    ->exists()
) {
    return true;
}

if ($company->projects()->whereHas('watchers', fn($q) => $q->where('users.id', $user->id))->exists()) {
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
    public function update(User $user, Company $company): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
   public function delete(User $user, Company $company): bool
{
    // только владелец компании
    return $user->id === $company->user_id;
}


    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Company $company): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Company $company): bool
    {
        //
    }
}
