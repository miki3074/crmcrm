<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatGroup extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'company_id', 'creator_id'];

    public function users() {
        return $this->belongsToMany(User::class, 'chat_group_user')
            ->withPivot('last_read_at');
    }

    public function messages() {
        return $this->hasMany(Message::class, 'group_id');
    }

}
