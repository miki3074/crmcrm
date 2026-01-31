<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'title',
        'assigned_to',
        'important',
        'completed',
        'created_by', // <--- ДОБАВИТЬ ЭТО
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function files()
    {
        return $this->hasMany(TaskChecklistFile::class, 'checklist_id');
    }

    public function assignees()
{
    return $this->belongsToMany(User::class, 'checklist_assignees');
}

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

}
