<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title','priority','start_date','due_date', 'description',
        'executor_id','responsible_id','project_id','company_id',
        'creator_id','progress','completed','completed_at', 'status'
    ];




    public function executor() {
        return $this->belongsTo(User::class, 'executor_id');
    }

    public function responsible() {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function project() {
        return $this->belongsTo(Project::class);
    }

    public function company() {
        return $this->belongsTo(Company::class);
    }

    public function creator() {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function files() {
        return $this->hasMany(TaskFile::class);
    }

    public function subtasks()
{
    return $this->hasMany(Subtask::class);
}


public function comments()
{
    return $this->hasMany(\App\Models\TaskComment::class)->latest();
}

public function checklists()
{
    return $this->hasMany(TaskChecklist::class);
}


public function subproject()
{
    return $this->belongsTo(Subproject::class);
}

protected $casts = [
        'completed' => 'boolean',
        'completed_at' => 'datetime',
    'start_date' => 'date',
    'due_date'   => 'date',
    ];

    // 🚀 Глобальный scope — исключает завершённые задачи
    protected static function booted()
    {
        static::addGlobalScope('not_completed', function (Builder $builder) {
            $builder->where('completed', false);
        });
    }


public function executors()
{
    return $this->belongsToMany(User::class, 'task_executors');
}

public function responsibles()
{
    return $this->belongsToMany(User::class, 'task_responsibles');
}


public function watcherstask()
{
    return $this->belongsToMany(User::class, 'task_user_watchers')
        ->withTimestamps()
        ->select('users.id', 'users.name', 'users.email');
}

public function watchers()
{
    return $this->belongsToMany(User::class, 'task_user_watchers')
        ->withTimestamps()
        ->select('users.id', 'users.name', 'users.email');
}

    public function producers()
    {
        return $this->belongsToMany(Producer::class, 'task_producer');
    }

    public function buyers()
    {
        return $this->belongsToMany(Buyer::class, 'task_buyer');
    }

    // 17.01.2026
    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            // 1. Создатель
            $q->where('creator_id', $userId)

                // 2. Исполнители или Ответственные (прямое участие)
                ->orWhereHas('executors', fn($sq) => $sq->where('users.id', $userId))
                ->orWhereHas('responsibles', fn($sq) => $sq->where('users.id', $userId))
                ->orWhereHas('watcherstask', fn($sq) => $sq->where('users.id', $userId))

                // 3. Через Проект (Менеджеры, Исполнители, Наблюдатели проекта)
                ->orWhereHas('project', function ($pq) use ($userId) {
                    $pq->whereHas('managers', fn($sq) => $sq->where('users.id', $userId))
                        ->orWhereHas('executors', fn($sq) => $sq->where('users.id', $userId))
                        ->orWhereHas('watchers', fn($sq) => $sq->where('users.id', $userId))
                        // 4. Владелец компании
                        ->orWhereHas('company', fn($sq) => $sq->where('user_id', $userId));
                });
        });
    }





}
