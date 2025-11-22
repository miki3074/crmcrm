<?php

// app/Models/SupportReply.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportReply extends Model
{
    use HasFactory;

    protected $fillable = ['support_message_id', 'user_id', 'reply'];

    public function message()
    {
        return $this->belongsTo(SupportMessage::class, 'support_message_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attachment()
{
    return $this->hasOne(SupportReplyAttachment::class);
}

}

