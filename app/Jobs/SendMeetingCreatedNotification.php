<?php

namespace App\Jobs;

use App\Models\Meeting;
use App\Models\User;
use App\Notifications\MeetingCreatedNotification;
use App\Traits\HandlesEmailErrors;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMeetingCreatedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, HandlesEmailErrors;

    protected $user;
    protected $meeting;
    protected $role;

    public function __construct(User $user, Meeting $meeting, string $role = 'participant')
    {
        $this->user = $user;
        $this->meeting = $meeting;
        $this->role = $role;
    }

    public function handle()
    {
        $this->safeNotify(
            $this->user,
            new MeetingCreatedNotification($this->meeting, $this->role)
        );
    }
}
