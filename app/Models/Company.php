<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'logo'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

//     public function employees()
// {
//     return $this->hasMany(\App\Models\User::class);
// }

 public function employees()
    {
        return $this->hasMany(User::class, 'company_id', 'id');
    }

public function users()
{
    return $this->hasMany(User::class);
}


public function storageManagers()
{
    return $this->belongsToMany(User::class, 'company_storage_managers')
        ->withTimestamps();
}

public function storageFiles()
{
    return $this->hasMany(StorageFile::class);
}
    
}
