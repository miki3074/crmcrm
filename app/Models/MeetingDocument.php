<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingDocument extends Model
{
    use HasFactory;

     protected $fillable = [
        'type',
        'number',
        'document_date',
        'task_id',
         'subtask_id',
        'created_by',
        'title',
        'body',
    ];

    protected $casts = [
        'document_date' => 'date',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }



public function subtask()
{
    return $this->belongsTo(Subtask::class);
}

}

