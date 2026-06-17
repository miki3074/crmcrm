<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poll extends Model
{
    protected $fillable = [
        'company_id',
        'created_by',
        'title',
        'description',
        'status',
        'closed_at'
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(PollParticipant::class);
    }

    public function problems(): HasMany
    {
        return $this->hasMany(PollProblem::class);
    }

    public function getParticipantsCountAttribute(): int
    {
        return $this->participants()->count();
    }

    public function getRespondedCountAttribute(): int
    {
        return $this->participants()->where('has_responded', true)->count();
    }

    public function getProblemsCountAttribute(): int
    {
        return $this->problems()->count();
    }

    public function isUserParticipant(int $userId): bool
    {
        return $this->participants()->where('user_id', $userId)->exists();
    }

    public function hasUserResponded(int $userId): bool
    {
        return $this->participants()
            ->where('user_id', $userId)
            ->where('has_responded', true)
            ->exists();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }
}
