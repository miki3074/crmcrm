<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubtaskFile extends Model
{
    use HasFactory;

    protected $fillable = ['subtask_id', 'user_id', 'filename', 'path','revision_comment','status',];

    public function subtask()
    {
        return $this->belongsTo(Subtask::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
