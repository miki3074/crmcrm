<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title','description','visibility','company_id','creator_id','start_at','end_at'
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at'   => 'datetime',
    ];

    public function company(){ return $this->belongsTo(Company::class); }
    public function creator(){ return $this->belongsTo(User::class,'creator_id'); }
    public function attendees(){ return $this->belongsToMany(User::class,'event_user'); }
}
