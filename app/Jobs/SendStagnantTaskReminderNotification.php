<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\User;
use App\Notifications\StagnantTaskReminderNotification;
use App\Traits\HandlesEmailErrors;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendStagnantTaskReminderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, HandlesEmailErrors;

    protected $user;
    protected $project;
    protected $tasks;
    protected $subtasks;

    public function __construct(User $user, Project $project, $tasks, $subtasks)
    {
        $this->user = $user;
        $this->project = $project;
        $this->tasks = $tasks;
        $this->subtasks = $subtasks;
    }

    public function handle()
    {
        $this->safeNotify(
            $this->user,
            new StagnantTaskReminderNotification($this->project, $this->tasks, $this->subtasks)
        );
    }
}
