<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubtaskFile extends Model
{
    use HasFactory;

    protected $fillable = ['subtask_id', 'user_id', 'filename', 'path','revision_comment','status', 'approval_status',];


    protected $casts = [
        'approved_at' => 'datetime',
    ];

    // Константы для нового статуса
    const APPROVAL_PENDING = 'pending';
    const APPROVAL_APPROVED = 'approved';
    const APPROVAL_REVISION = 'revision';

    // Геттер для удобства
    public function getApprovalStatusLabelAttribute()
    {
        return [
            'pending' => 'Ожидает согласования',
            'approved' => 'Согласован',
            'revision' => 'Требуется доработка',
        ][$this->approval_status] ?? 'Неизвестно';
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function subtask()
    {
        return $this->belongsTo(Subtask::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
