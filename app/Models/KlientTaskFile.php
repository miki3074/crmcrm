<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KlientTaskFile extends Model
{
    use HasFactory;

    protected $fillable = ['klient_task_id', 'user_id', 'original_name', 'file_path', 'file_size'];

    protected $appends = ['file_url'];

    public function klientTask()
    {
        return $this->belongsTo(KlientTask::class, 'klient_task_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFileUrlAttribute()
    {
        return route('klient-task-files.download', $this->id);
    }
}
