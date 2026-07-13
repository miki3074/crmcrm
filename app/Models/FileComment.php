<?php
// app/Models/FileComment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileComment extends Model
{
    protected $fillable = [
        'task_file_id',
        'user_id',
        'comment',
        'type'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function taskFile(): BelongsTo
    {
        return $this->belongsTo(TaskFile::class, 'task_file_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 🔥 Форматированная дата
    public function getFormattedDateAttribute()
    {
        return $this->created_at->format('d.m.Y H:i');
    }

    // 🔥 Получить имя пользователя
    public function getUserNameAttribute()
    {
        return $this->user->name ?? 'Неизвестный';
    }
}
