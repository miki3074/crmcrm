<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeArticle extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'company_id',
        'folder_id',
        'created_by',
        'updated_by',
        'title',
        'content',
        'content_text',
        'status',
        'position',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'folder_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'content' => 'array',
        'position' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(
            KnowledgeFolder::class,
            'folder_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    public function files(): HasMany
    {
        return $this->hasMany(
            KnowledgeFile::class,
            'article_id'
        );
    }
}