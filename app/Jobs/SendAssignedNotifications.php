<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\User;
use App\Notifications\UserAssignedToTaskNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Traits\HandlesEmailErrors;

class SendAssignedNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, HandlesEmailErrors;

    protected $task;
    protected $userIds;
    protected $role;

    public function __construct(Task $task, array $userIds, string $role)
    {
        $this->task = $task;
        $this->userIds = $userIds;
        $this->role = $role;
    }

    public function handle()
    {
        foreach ($this->userIds as $userId) {
            $user = User::find($userId);
            if ($user && $user->email) {
                // ✅ ИСПРАВЛЕНО: используем safeNotify из трейта
                $this->safeNotify(
                    $user,
                    new UserAssignedToTaskNotification($this->task, $this->role)
                );
            }
        }
    }
}
