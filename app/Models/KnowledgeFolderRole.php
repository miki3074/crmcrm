<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeFolderRole extends Model
{
    protected $fillable = [
        'folder_id',
        'user_id',
        'role',
        'assigned_by',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(
            KnowledgeFolder::class,
            'folder_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}