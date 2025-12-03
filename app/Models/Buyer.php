<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Buyer extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'created_by', 'company_id'];


    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_buyer');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

}
