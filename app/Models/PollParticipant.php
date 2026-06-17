<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PollParticipant extends Model
{
    protected $fillable = [
        'poll_id',
        'user_id',
        'has_responded',
        'responded_at'
    ];

    protected $casts = [
        'has_responded' => 'boolean',
        'responded_at' => 'datetime',
    ];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(Poll::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsResponded(): void
    {
        $this->update([
            'has_responded' => true,
            'responded_at' => now()
        ]);
    }
}
