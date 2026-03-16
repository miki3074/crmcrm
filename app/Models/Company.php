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
        return $this->belongsTo(User::class)
        ->withPivot('role', 'created_by')
                ->withTimestamps();
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

//     public function employees()
// {
//     return $this->hasMany(\App\Models\User::class);
// }

//  public function employees()
//     {
//         return $this->hasMany(User::class, 'company_id', 'id');
//     }

public function users()
{
    return $this->belongsToMany(\App\Models\User::class, 'company_user')
                ->withPivot('role', 'created_by')
                ->withTimestamps();
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

public function managers()
{
    return $this->belongsToMany(User::class, 'company_user')
        ->wherePivot('role', 'manager')
        ->select('users.id', 'users.name')
        ->withTimestamps();
}

    public function allParticipants()
    {
        // Получаем сотрудников из pivot таблицы
        $users = $this->users()->get();

        // Добавляем владельца, если его нет в списке
        $owner = \App\Models\User::find($this->user_id);
        if (!$users->contains('id', $owner->id)) {
            $users->push($owner);
        }

        return $users;
    }

    public function scopeRelatedToUser($query, $userId)
    {
        return $query->where('user_id', $userId)
            ->orWhereHas('users', fn($q) => $q->where('user_id', $userId))
            ->orWhereHas('projects', function ($q) use ($userId) {
                $q->whereHas('managers', fn($m) => $m->where('users.id', $userId))
                    ->orWhereHas('executors', fn($e) => $e->where('users.id', $userId))
                    ->orWhereHas('watchers', fn($w) => $w->where('users.id', $userId))
                    ->orWhereHas('tasks', fn($t) => $t->forUser($userId));
            });
    }

// Авто-удаление связанных файлов при удалении компании
    protected static function booted()
    {
        static::deleting(function ($company) {
            $company->projects->each(function ($project) {
                $project->tasks->each(function ($task) {
                    foreach ($task->files as $file) {
                        \Storage::disk('public')->delete($file->file_path);
                        $file->delete();
                    }
                    $task->subtasks()->delete();
                    $task->delete();
                });
                $project->delete();
            });
        });
    }


}
