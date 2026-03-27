<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KlientTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'klient_id', 'creator_id', 'responsible_id',
        'title', 'description', 'deadline', 'priority', 'type', 'status'
    ];

    public function klient() { return $this->belongsTo(Klient::class); }
    public function creator() { return $this->belongsTo(User::class, 'creator_id'); }
    public function responsible() { return $this->belongsTo(User::class, 'responsible_id'); }
    public function files() { return $this->hasMany(KlientTaskFile::class); }
}
