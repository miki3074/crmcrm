<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Subtask extends Model
{
    use HasFactory;


    protected $fillable = [
        'task_id',
        'title',
        // 'executor_id',
         'description',
        'start_date',
        'due_date',
        'progress',
        'creator_id',
        'completed',
        'completed_at',
        'status'
    ];

    protected $casts = [
    'progress' => 'integer',
    'completed' => 'boolean',
    'completed_at' => 'datetime',
];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    // public function executor()
    // {
    //     return $this->belongsTo(User::class, 'executor_id');
    // }

    public function creator()
{
    return $this->belongsTo(User::class, 'creator_id');
}

public function executors()
{
    return $this->belongsToMany(User::class, 'subtask_executors');
}

public function responsibles()
{
    return $this->belongsToMany(User::class, 'subtask_responsibles');
}


public function files()
{
    return $this->hasMany(\App\Models\SubtaskFile::class);
}

public function parent()
{
    return $this->belongsTo(Subtask::class, 'parent_id');
}

public function children()
{
    return $this->hasMany(Subtask::class, 'parent_id');
}

public function comments()
{
    return $this->hasMany(SubtaskComment::class);
}

// public function checklists()
// {
//     return $this->hasMany(SubtaskChecklist::class);
// }
public function checklist()
{
    return $this->hasMany(SubtaskChecklist::class);
}

    protected static function booted()
    {
        static::addGlobalScope('not_completed', function (Builder $builder) {
            $builder->where('completed', false);
        });
    }

// 17.01.2026

    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            // 1. Создатель подзадачи
            $q->where('creator_id', $userId)

                // 2. Участник подзадачи
                ->orWhereHas('executors', fn($sq) => $sq->where('users.id', $userId))
                ->orWhereHas('responsibles', fn($sq) => $sq->where('users.id', $userId))

                // 3. Доступ через Родительскую Задачу (наследуем права задачи)
                ->orWhereHas('task', function ($tq) use ($userId) {
                    $tq->where('creator_id', $userId)
                        ->orWhereHas('executors', fn($sq) => $sq->where('users.id', $userId))
                        ->orWhereHas('responsibles', fn($sq) => $sq->where('users.id', $userId))
                        // Проект и компания
                        ->orWhereHas('project', function ($pq) use ($userId) {
                            $pq->whereHas('managers', fn($sq) => $sq->where('users.id', $userId))
                                ->orWhereHas('executors', fn($sq) => $sq->where('users.id', $userId))
                                ->orWhereHas('company', fn($sq) => $sq->where('user_id', $userId));
                        });
                });
        });
    }


}
