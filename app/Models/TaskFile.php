<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskFile extends Model
{
    use HasFactory;

      protected $fillable = [
          'task_id',
      'file_path',
      'user_id',
    'file_name',
    'status',
    'rejection_reason'
      ];




    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 🔥 Отношение к комментариям
    public function comments(): HasMany
    {
        return $this->hasMany(FileComment::class, 'task_file_id');
    }

    // 🔥 Получить комментарии с типом 'rejection'
    public function rejectionComments(): HasMany
    {
        return $this->hasMany(FileComment::class, 'task_file_id')->where('type', 'rejection');
    }

    // 🔥 Получить все комментарии (сортировка по дате)
    public function getCommentsSortedAttribute()
    {
        return $this->comments()->orderBy('created_at', 'desc')->get();
    }
}
