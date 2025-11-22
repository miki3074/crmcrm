<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportReplyAttachment extends Model
{
    protected $fillable = [
        'support_reply_id',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function reply()
    {
        return $this->belongsTo(SupportReply::class, 'support_reply_id');
    }
}
