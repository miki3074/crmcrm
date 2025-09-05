<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interaction extends Model
{
    use HasFactory;

    protected $fillable = ['client_id','type','content','interaction_date','user_id'];
    protected $casts = ['interaction_date' => 'datetime'];
    public function client(){ return $this->belongsTo(Client::class); }
    public function user(){ return $this->belongsTo(User::class); }
}
