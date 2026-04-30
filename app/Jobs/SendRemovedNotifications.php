<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\User;
use App\Notifications\UserRemovedFromTaskNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Traits\HandlesEmailErrors;

class SendRemovedNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, HandlesEmailErrors;

    protected $task;
    protected $userId;
    protected $role;

    public function __construct(Task $task, int $userId, string $role)
    {
        $this->task = $task;
        $this->userId = $userId;
        $this->role = $role;
    }

    public function handle()
    {
        $user = User::find($this->userId);
        if ($user && $user->email) {
            // ✅ Используем safeNotify
            $this->safeNotify(
                $user,
                new UserRemovedFromTaskNotification($this->task, $this->role)
            );
        }
    }
}
