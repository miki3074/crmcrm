<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubtaskComment extends Model
{
    use HasFactory;

     protected $fillable = [
        'subtask_id',
        'user_id',
        'comment',
         'parent_id'
    ];

    public function subtask()
    {
        return $this->belongsTo(Subtask::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(SubtaskComment::class, 'parent_id');
    }

    protected $casts = [
    'mentions' => 'array',
];

}
