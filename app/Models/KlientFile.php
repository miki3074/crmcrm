<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KlientFile extends Model
{
    use HasFactory;

    protected $fillable = ['klient_id', 'user_id', 'original_name', 'file_path', 'file_size'];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
