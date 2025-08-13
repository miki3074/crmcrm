<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'priority', 'start_date', 'due_date',
        'executor_id', 'responsible_id', 'project_id',
        'company_id', 'creator_id',  'progress',
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

}
