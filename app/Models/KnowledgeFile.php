<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeFile extends Model
{
    protected $fillable = [
        'company_id',
        'folder_id',
        'article_id',
        'uploaded_by',
        'disk',
        'path',
        'original_name',
        'stored_name',
        'mime_type',
        'extension',
        'size',
        'category',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(
            KnowledgeFolder::class,
        'folder_id'
        );
    }


 protected $casts = [
        'size' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(
            KnowledgeArticle::class,
            'article_id'
        );
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'uploaded_by'
        );
    }

   


}