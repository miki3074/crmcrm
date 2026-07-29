<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeFolder extends Model
{
    protected $fillable = [
        'company_id',
        'parent_id',
        'created_by',
        'name',
        'access_type',
         'access_mode',
        'position',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'parent_id'
        );
    }

    public function children(): HasMany
    {
        return $this
            ->hasMany(
                self::class,
                'parent_id'
            )
            ->orderBy('position')
            ->orderBy('name');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function roles(): HasMany
    {
        return $this->hasMany(
            KnowledgeFolderRole::class,
            'folder_id'
        );
    }

    public function articles(): HasMany
{
    return $this
        ->hasMany(
            KnowledgeArticle::class,
            'folder_id'
        )
        ->orderBy('position')
        ->orderBy('title');
}

public function files(): HasMany
{
    return $this
        ->hasMany(
            KnowledgeFile::class,
            'folder_id'
        )
        ->latest();
}

   
}