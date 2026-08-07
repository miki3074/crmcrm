<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = [
        'name',
    ];

    public function radioStations()
    {
        return $this->hasMany(RadioStation::class);
    }

    public function mediaPlans()
    {
        return $this->belongsToMany(
            MediaPlan::class,
            'media_plan_city'
        );
    }
}