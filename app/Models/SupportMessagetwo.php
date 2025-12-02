<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportMessagetwo extends Model
{
    use HasFactory;

    protected $table = 'support_messagestwo'; 

      protected $fillable = [
        'thread_id',
        'user_id',
        'body',
        'is_support',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function thread()
    {
        return $this->belongsTo(SupportThread::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attachments()
    {
        return $this->hasMany(SupportAttachmenttwo::class, 'message_id');
    }
}
