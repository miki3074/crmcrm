<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = ['company_id','name','phone','email','notes'];

    public function company(){ return $this->belongsTo(Company::class); }
    public function interactions(){ return $this->hasMany(Interaction::class)->latest('interaction_date'); }
    public function deals(){ return $this->hasMany(Deal::class)->latest(); }
}
