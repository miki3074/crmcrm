<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'counterparty',
        'amount',
        'margin',
        'status',
        'signed_at',
        'valid_until',
        'file_path',
        'file_name',
        'created_by',
        'task_id',
        'subtask_id',
    ];

    protected $casts = [
        'signed_at' => 'date',
        'valid_until' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function files()
    {
        return $this->hasMany(ContractFile::class);
    }

    public function task() {
        return $this->belongsTo(Task::class);
    }
    public function subtask() {
        return $this->belongsTo(Subtask::class);
    }



    }
