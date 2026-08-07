<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;


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
        'email_verified_at',
        'is_active',
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
        'login_code_expires_at' => 'datetime',

    ];

    public function canLogin(): bool
    {
        // Если email_verified_at заполнен - значит пользователь уже подтвержден
        if ($this->email_verified_at) {
            return true;
        }

        // Если is_active = true - пользователь активирован
        if ($this->is_active) {
            return true;
        }

        // Для старых пользователей без этих полей - разрешаем вход
        // (обратная совместимость)
        if (is_null($this->email_verified_at) && is_null($this->is_active)) {
            return true;
        }

        return false;
    }

    /**
     * Проверка активен ли пользователь
     */


    public function companies()
        {
            return $this->hasMany(Company::class);
        }

    public function companiesmess()
    {
        return $this->belongsToMany(Company::class, 'company_user');
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

    public function supportThreads()
    {
        // Важно указать второй аргумент 'support_user_id',
        // так как Laravel по умолчанию ищет 'user_id'
        return $this->hasMany(SupportThread::class, 'support_user_id');
    }

    public function ownedCompanies()
    {
        return $this->hasMany(Company::class, 'user_id');
    }

    public function workingCompanies()
    {
        // Связь через company_user
        return $this->belongsToMany(Company::class, 'company_user', 'user_id', 'company_id')
            ->withPivot('role');
    }

// Хелпер, чтобы получить ВСЕ компании (где владелец + где сотрудник)
    public function getAllCompaniesAttribute()
    {
        return $this->ownedCompanies->merge($this->workingCompanies);
    }

    public function chatGroups() {
        return $this->belongsToMany(ChatGroup::class, 'chat_group_user');
    }


    public function flutterMessages() {
        return $this->hasMany(FlutterMessage::class);
    }

    public function hasVerifiedEmail()
    {
        return !is_null($this->email_verified_at);
    }

    public function markEmailAsVerified()
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    // ============ НОВЫЕ МЕТОДЫ ДЛЯ ОПРОСОВ ============

    /**
     * Связь с опросами, которые создал пользователь
     */
    public function createdPolls(): HasMany
    {
        return $this->hasMany(Poll::class, 'created_by');
    }

    /**
     * Связь с участием в опросах
     */
    public function pollParticipants(): HasMany
    {
        return $this->hasMany(PollParticipant::class);
    }

    /**
     * Связь с проблемами, которые создал пользователь
     */
    public function pollProblems(): HasMany
    {
        return $this->hasMany(PollProblem::class);
    }

    /**
     * Связь с комментариями к проблемам
     */
    public function pollProblemComments(): HasMany
    {
        return $this->hasMany(PollProblemComment::class);
    }

    /**
     * Получить активные опросы, в которых участвует пользователь
     */
    public function activePolls()
    {
        return Poll::whereHas('participants', function ($query) {
            $query->where('user_id', $this->id);
        })->where('status', 'active')->get();
    }

    /**
     * Получить завершенные опросы, в которых участвовал пользователь
     */
    public function completedPolls()
    {
        return Poll::whereHas('participants', function ($query) {
            $query->where('user_id', $this->id);
        })->where('status', 'closed')->get();
    }

    /**
     * Проверить, участвует ли пользователь в опросе
     */
    public function isPollParticipant(int $pollId): bool
    {
        return PollParticipant::where('poll_id', $pollId)
            ->where('user_id', $this->id)
            ->exists();
    }

    /**
     * Проверить, ответил ли пользователь на опрос
     */
    public function hasRespondedToPoll(int $pollId): bool
    {
        return PollParticipant::where('poll_id', $pollId)
            ->where('user_id', $this->id)
            ->where('has_responded', true)
            ->exists();
    }

    /**
     * Получить все опросы компании пользователя
     */
    public function getCompanyPolls()
    {
        if ($this->company_id) {
            return Poll::where('company_id', $this->company_id)->get();
        }
        return collect();
    }

    /**
     * Получить количество опросов, в которых участвует пользователь
     */
    public function getPollParticipationCountAttribute(): int
    {
        return $this->pollParticipants()->count();
    }

    /**
     * Получить количество созданных опросов
     */
    public function getCreatedPollsCountAttribute(): int
    {
        return $this->createdPolls()->count();
    }

    /**
     * Получить количество решенных проблем
     */
    public function getResolvedProblemsCountAttribute(): int
    {
        return $this->pollProblems()->where('is_resolved', true)->count();
    }

    /**
     * Получить роль пользователя в компании
     */
    public function getRoleInCompany(int $companyId): ?string
    {
        $companyUser = \DB::table('company_user')
            ->where('company_id', $companyId)
            ->where('user_id', $this->id)
            ->first();

        return $companyUser ? $companyUser->role : null;
    }

    /**
     * Проверить, является ли пользователь владельцем компании
     */
    public function isOwnerOfCompany(int $companyId): bool
    {
        return $this->getRoleInCompany($companyId) === 'owner';
    }

    /**
     * Проверить, является ли пользователь менеджером компании
     */
    public function isManagerOfCompany(int $companyId): bool
    {
        $role = $this->getRoleInCompany($companyId);
        return $role === 'manager' || $role === 'owner';
    }

    /**
     * Получить всех пользователей компании (кроме себя)
     */
    public function getCompanyMembers(int $companyId)
    {
        return User::whereHas('attachedCompanies', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->where('id', '!=', $this->id)->get();
    }

    /**
     * Проверить, является ли пользователь участником компании
     */
    public function isMemberOfCompany(int $companyId): bool
    {
        return $this->attachedCompanies()
            ->where('company_id', $companyId)
            ->exists();
    }

    /**
     * Получить всех сотрудников компании (без владельца)
     */
    public function getCompanyEmployees(int $companyId)
    {
        return User::whereHas('attachedCompanies', function ($query) use ($companyId) {
            $query->where('company_id', $companyId)
                ->where('role', '!=', 'owner');
        })->get();
    }

    /**
     * Получить данные о компании пользователя
     */
    public function getCurrentCompany()
    {
        if ($this->company_id) {
            return Company::find($this->company_id);
        }
        return $this->companies()->first();
    }


}
