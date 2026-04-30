<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectWatcherAddedNotification;
use App\Traits\HandlesEmailErrors;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendProjectWatcherAddedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, HandlesEmailErrors;

    protected $user;
    protected $project;
    protected $company;

    public function __construct(User $user, Project $project, Company $company)
    {
        $this->user = $user;
        $this->project = $project;
        $this->company = $company;
    }

    public function handle()
    {
        $this->safeNotify(
            $this->user,
            new ProjectWatcherAddedNotification($this->project, $this->company)
        );
    }
}
