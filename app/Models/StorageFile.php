<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorageFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'uploader_id', 'original_name', 'path', 'size', 'visibility'
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function uploader() { return $this->belongsTo(User::class, 'uploader_id'); }

    public function allowedUsers()
    {
        return $this->belongsToMany(User::class, 'storage_file_user')->withTimestamps();
    }
}
