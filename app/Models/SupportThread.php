<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportThread extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'subject', 'support_user_id', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(SupportMessagetwo::class, 'thread_id')
            ->orderBy('created_at');
    }

    public function supportAgent()
    {
        return $this->belongsTo(User::class, 'support_user_id');
    }
}
