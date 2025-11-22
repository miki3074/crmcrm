<?php

// app/Models/SupportMessage.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'page_url', 'email', 'telegram_chat_id', 'message', 'status', 'assigned_support_id','last_read_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function replies()
    {
        return $this->hasMany(SupportReply::class);
    }

    public function attachments()
{
    return $this->hasMany(\App\Models\SupportAttachment::class);
}


public function getHasUnreadAttribute()
{
    $lastReply = $this->replies()->latest()->first();

    return $lastReply
        ? !$this->last_read_at || $lastReply->created_at > $this->last_read_at
        : false;
}


public function getUserHasUnreadAttribute()
{
    $lastReply = $this->replies()->latest()->first();

    if (!$lastReply) return false;

    return $lastReply->user->hasRole('support') &&
           (!$this->user_last_read || $lastReply->created_at > $this->user_last_read);
}

public function getSupportHasUnreadAttribute()
{
    $lastReply = $this->replies()->latest()->first();

    if (!$lastReply) return false;

    return !$lastReply->user->hasRole('support') &&
           (!$this->support_last_read || $lastReply->created_at > $this->support_last_read);
}



}

