<?php

namespace App\Jobs;

use App\Models\CalendarEvent;
use App\Models\Company;
use App\Models\User;
use App\Notifications\EventCreatedNotification;
use App\Traits\HandlesEmailErrors;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEventCreatedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, HandlesEmailErrors;

    protected $user;
    protected $event;
    protected $company;

    public function __construct(User $user, CalendarEvent $event, Company $company)
    {
        $this->user = $user;
        $this->event = $event;
        $this->company = $company;
    }

    public function handle()
    {
        $this->safeNotify(
            $this->user,
            new EventCreatedNotification($this->event, $this->company)
        );
    }
}
