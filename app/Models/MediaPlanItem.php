<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaPlanItem extends Model
{
    protected $fillable = [
    'media_plan_id',
    'sort_order',
    'city_id',
    'radio_station_id',
    'type',
    'platform_name',

    'format',
    'materials_url',

    'duration_seconds',
    'outputs_per_day',
    'days_count',
    'total_outputs',

    'price_per_second',
    'total_price',

    'kpi',
    'responsible_text',

    'start_date',
    'end_date',
];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'price_per_second' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function mediaPlan()
    {
        return $this->belongsTo(MediaPlan::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function radioStation()
    {
        return $this->belongsTo(RadioStation::class);
    }

    public function responsibles()
{
    return $this->belongsToMany(
        User::class,
        'media_plan_item_user',
        'media_plan_item_id',
        'user_id'
    )->withTimestamps();
}
}