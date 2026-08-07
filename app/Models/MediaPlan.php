<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaPlan extends Model
{
    protected $fillable = [
        'klient_id',
        'creator_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
        'total_amount',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function klient()
    {
        return $this->belongsTo(Klient::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function cities()
    {
        return $this->belongsToMany(
            City::class,
            'media_plan_city'
        );
    }

    public function items()
    {
        return $this->hasMany(MediaPlanItem::class);
    }
}