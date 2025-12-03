<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskTemplateFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_template_id',
        'file_path',
        'file_name',
        'mime_type',
        'uploaded_by',
    ];

    public function template()
    {
        return $this->belongsTo(TaskTemplate::class, 'task_template_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
