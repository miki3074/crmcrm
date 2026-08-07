<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadioStation extends Model
{
    protected $fillable = [
        'city_id',
        'name',
        'frequency',
        'price_per_second',
    ];

    protected $casts = [
        'price_per_second' => 'decimal:2',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}