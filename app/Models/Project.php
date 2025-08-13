<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
    'name',
    'company_id',
    'initiator_id',
    'manager_id',
    'start_date',
    'duration_days',
    'budget',
    'description',
    ];

    public function company() {
    return $this->belongsTo(Company::class);
}

    public function initiator() {
        return $this->belongsTo(User::class, 'initiator_id');
}

    public function manager() {
        return $this->belongsTo(User::class, 'manager_id');
}

public function tasks()
{
    return $this->hasMany(\App\Models\Task::class);
}

}
