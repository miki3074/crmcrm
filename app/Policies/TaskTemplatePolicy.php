<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\TaskTemplate;
use App\Models\User;

class TaskTemplatePolicy
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
    public function view(User $user, TaskTemplate $taskTemplate): bool
    {
        //
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
    public function update(User $user, TaskTemplate $taskTemplate): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TaskTemplate $taskTemplate): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TaskTemplate $taskTemplate): bool
    {
        //
    }

    public function createTemplate(User $user, $companyId)
    {
        $company = Company::find($companyId);

        // владелец компании
        if ($company->user_id === $user->id) return true;

        // менеджер компании
        if ($company->users()->wherePivot('role', 'manager')->where('users.id', $user->id)->exists())
            return true;

        return false;
    }

    public function useTemplate(User $user, TaskTemplate $template)
    {
        return
            $template->company->user_id === $user->id ||
            $template->company->users()
                ->wherePivot('role','manager')
                ->where('users.id',$user->id)->exists();
    }


    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TaskTemplate $taskTemplate): bool
    {
        //
    }
}
