<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PollProblemComment extends Model
{
    protected $fillable = [
        'poll_problem_id',
        'user_id',
        'comment'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function problem(): BelongsTo
    {
        return $this->belongsTo(PollProblem::class, 'poll_problem_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
