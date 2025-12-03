<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'project_id',
        'creator_id',
        'title',
        'description',
        'executor_ids',
        'responsible_ids',
        'watcher_ids',
        'due_in_days',
        'priority',
        'producer_id',
        'buyer_id'
    ];

    protected $casts = [
        'executor_ids'    => 'array',
        'responsible_ids' => 'array',
        'watcher_ids'     => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function files()
    {
        return $this->hasMany(TaskTemplateFile::class);
    }

    public function producer()
    {
        return $this->belongsTo(Producer::class);
    }

    public function buyer()
    {
        return $this->belongsTo(Buyer::class);
    }

}
