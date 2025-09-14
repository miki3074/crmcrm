<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskChecklistFile extends Model
{
    use HasFactory;

    protected $fillable = ['checklist_id', 'file_path'];

    public function checklist()
    {
        return $this->belongsTo(TaskChecklist::class);
    }
}
