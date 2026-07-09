<?php
// app/Policies/UserPolicy.php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    protected $allowedCreate = ['dir@npoenergoteh.ru', 'miki23074@gmail.com'];
    protected $allowedEditDelete = ['miki23074@gmail.com'];
    protected $allowedView = ['dir@npoenergoteh.ru', 'miki23074@gmail.com'];

    /**
     * Determine whether the user can view any models.
     * 🔥 Только пользователи из списка могут просматривать
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->email, $this->allowedView);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return in_array($user->email, $this->allowedView);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->email, $this->allowedCreate);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Только miki23074@gmail.com может редактировать
        if (!in_array($user->email, $this->allowedEditDelete)) {
            return false;
        }

        // Нельзя редактировать самого себя
        if ($user->id === $model->id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Только miki23074@gmail.com может удалять
        if (!in_array($user->email, $this->allowedEditDelete)) {
            return false;
        }

        // Нельзя удалять самого себя
        if ($user->id === $model->id) {
            return false;
        }

        // Нельзя удалять miki23074@gmail.com
        if ($model->email === 'miki23074@gmail.com') {
            return false;
        }

        return true;
    }
}
