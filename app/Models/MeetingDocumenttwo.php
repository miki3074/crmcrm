<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingDocumenttwo extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Связь с совещанием
    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    // Связь с автором (кто загрузил)
    public function uploader()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Аксессор для красивого размера файла
    public function getHumanSizeAttribute()
    {
        $bytes = $this->size;
        if ($bytes == 0) return '0 B';
        $i = floor(log($bytes) / log(1024));
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        return sprintf('%.2f %s', $bytes / pow(1024, $i), $sizes[$i]);
    }
}
