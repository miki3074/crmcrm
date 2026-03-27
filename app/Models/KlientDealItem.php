<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KlientDealItem extends Model
{
    use HasFactory;

    protected $fillable = ['klient_deal_id', 'name', 'quantity', 'unit_price', 'total_price'];

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($item) {
            $item->total_price = $item->quantity * $item->unit_price;
        });
    }
}
