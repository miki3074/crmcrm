<?php

namespace App\Jobs;

use App\Models\Subtask;
use App\Models\Task;
use App\Models\User;
use App\Notifications\SubtaskAssignedNotification;
use App\Traits\HandlesEmailErrors;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSubtaskAssignedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, HandlesEmailErrors;

    protected $user;
    protected $subtask;
    protected $task;
    protected $role;

    public function __construct(User $user, Subtask $subtask, Task $task, string $role)
    {
        $this->user = $user;
        $this->subtask = $subtask;
        $this->task = $task;
        $this->role = $role;
    }

    public function handle()
    {
        $this->safeNotify(
            $this->user,
            new SubtaskAssignedNotification($this->subtask, $this->task, $this->role)
        );
    }
}
