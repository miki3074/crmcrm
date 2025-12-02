<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportAttachmenttwo extends Model
{
    use HasFactory;

    protected $table = 'support_attachmentstwo'; 

     protected $fillable = [
        'message_id',
        'path',
        'mime_type',
        'original_name',
        'size',
    ];

    public function message()
    {
        return $this->belongsTo(SupportMessagetwo::class);
    }
}
