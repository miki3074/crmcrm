<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectManagerReplacedNotification;
use App\Traits\HandlesEmailErrors;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendProjectManagerReplacedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, HandlesEmailErrors;

    protected $user;
    protected $project;
    protected $company;
    protected $oldManagerName;

    public function __construct(User $user, Project $project, Company $company, string $oldManagerName)
    {
        $this->user = $user;
        $this->project = $project;
        $this->company = $company;
        $this->oldManagerName = $oldManagerName;
    }

    public function handle()
    {
        $this->safeNotify(
            $this->user,
            new ProjectManagerReplacedNotification($this->project, $this->company, $this->oldManagerName)
        );
    }
}
