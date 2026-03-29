<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class FlutterMessage extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'message'];

    // Связь: сообщение принадлежит пользователю
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
