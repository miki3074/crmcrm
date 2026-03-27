<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Klient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'status', 'segment', 'rating', 'phone', 'email',
        'messengers', 'inn', 'kpp', 'ogrn', 'legal_address',
        'actual_address', 'industry', 'user_id', 'company_id', 'project_id', 'task_id', 'user_id'
    ];

    protected $casts = [
        'messengers' => 'array',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function task() { return $this->belongsTo(Task::class); }


    public function contactPersons(): HasMany
    {
        return $this->hasMany(KlientContactPerson::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function allowedUsers()
    {
        return $this->belongsToMany(User::class, 'klient_access', 'klient_id', 'user_id');
    }

    public function files()
    {
        return $this->hasMany(KlientFile::class);
    }

    public function tasks() {
        return $this->hasMany(KlientTask::class);
    }

    public function deals()
    {
        return $this->hasMany(KlientDeal::class);
    }
}
