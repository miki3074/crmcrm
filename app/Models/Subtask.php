<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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





}
