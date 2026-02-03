<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'creator_id',
        'responsible_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'agenda',
        'minutes',
        'rejection_reason',
        'status',

        // Добавьте эти два поля:
        'task_id',
        'subtask_id',
    ];

    protected $guarded = [];


    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function subtask()
    {
        return $this->belongsTo(Subtask::class);
    }


    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    // ======================================

    public function participants()
    {
        // Объединяем всё в одну правильную связь
        return $this->belongsToMany(User::class, 'meeting_participants')
            ->withPivot('status', 'is_signed', 'agenda_status', 'agenda_comment') // <-- Все поля здесь
            ->withTimestamps();
    }



    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function documents()
    {
        return $this->hasMany(MeetingDocumenttwo::class);
    }
}
