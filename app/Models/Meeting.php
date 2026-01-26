<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    use HasFactory;

    protected $guarded = [];





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
