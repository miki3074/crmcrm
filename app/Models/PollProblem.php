<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PollProblem extends Model
{
    protected $fillable = [
        'poll_id',
        'user_id',
        'problem',
        'solution',
        'is_resolved',
        'resolved_at'
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PollProblemComment::class);
    }

    public function resolve(): void
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now()
        ]);
    }

    public function addComment(User $user, string $commentText): PollProblemComment
    {
        return $this->comments()->create([
            'user_id' => $user->id,
            'comment' => $commentText
        ]);
    }
}
