<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subproject extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'title', 'responsible_id'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
