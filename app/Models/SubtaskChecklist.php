<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubtaskChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'subtask_id',
        'title',
        'completed',
        'responsible_id',
        'creator_id',
    ];

    public function subtask()
    {
        return $this->belongsTo(Subtask::class);
    }

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
