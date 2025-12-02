<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'counterparty',
        'amount',
        'status',
        'signed_at',
        'file_path',
        'file_name',
        'created_by',
    ];

    protected $casts = [
        'signed_at' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function files()
    {
        return $this->hasMany(ContractFile::class);
    }



    }
