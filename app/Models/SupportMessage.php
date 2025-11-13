<?php

// app/Models/SupportMessage.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'page_url', 'email', 'telegram_chat_id', 'message', 'status', 'assigned_support_id',
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
}

