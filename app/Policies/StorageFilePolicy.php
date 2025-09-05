<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\StorageFile;
use App\Models\User;

class StorageFilePolicy
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
    public function view(User $user, StorageFile $file): bool
    {
        // владелец компании
        if ($file->company && $file->company->user_id === $user->id) return true;

        // менеджер хранилища
        if ($file->company && $file->company
                ->storageManagers()
                ->where('users.id', $user->id)
                ->exists()) return true;

        // загрузчик
        if ($file->uploader_id === $user->id) return true;

        // видимость для всех сотрудников компании
        if ($file->visibility === 'company_all' && $user->company_id === $file->company_id) return true;

        // точечный доступ
        if ($file->allowedUsers()->where('users.id', $user->id)->exists()) return true;

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, \App\Models\Company $company): bool
    {
        return $company->user_id === $user->id
            || $company->storageManagers()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, StorageFile $storageFile): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StorageFile $file): bool
    {
        return $file->uploader_id === $user->id
            || ($file->company && $file->company->user_id === $user->id)
            || ($file->company && $file->company->storageManagers()->where('users.id',$user->id)->exists());
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, StorageFile $storageFile): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, StorageFile $storageFile): bool
    {
        //
    }
}
