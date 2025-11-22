<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'created_by', 
        'company_id',
         'telegram_chat_id', 
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function companies()
        {
            return $this->hasMany(Company::class);
        }

    public function employees()
        {
            return $this->hasMany(User::class, 'created_by');
        }

        public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }


public function managedStorages()
{
    return $this->belongsToMany(Company::class, 'company_storage_managers')
        ->withTimestamps();
}

public function uploadedStorageFiles()
{
    return $this->hasMany(StorageFile::class, 'uploader_id');
}

public function accessibleStorageFiles()
{
    // для выборочного доступа
    return $this->belongsToMany(StorageFile::class, 'storage_file_user')
        ->withTimestamps();
}


public function storageManagedCompanies()
{
    return $this->belongsToMany(Company::class, 'company_storage_managers')->withTimestamps();
}

public function attachedCompanies()
{
    // company_user — имя pivot таблицы
    return $this->belongsToMany(Company::class, 'company_user')
                ->withPivot('role', 'created_by')
                ->withTimestamps();
}

public function managedProjects()
{
    return $this->belongsToMany(Project::class, 'project_user');
}


public function watchingProjects()
{
    return $this->belongsToMany(Project::class, 'project_watchers')->withTimestamps();
}

public function supportMessagesAssigned()
{
    return $this->hasMany(SupportMessage::class, 'assigned_support_id');
}


}
