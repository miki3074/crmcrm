<?php

namespace App\Jobs;

use App\Models\SupportThread;
use App\Models\User;
use App\Notifications\NewSupportTicketNotification;
use App\Traits\HandlesEmailErrors;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNewSupportTicketNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, HandlesEmailErrors;

    protected $user;
    protected $thread;
    protected $client;

    public function __construct(User $user, SupportThread $thread, User $client)
    {
        $this->user = $user;
        $this->thread = $thread;
        $this->client = $client;
    }

    public function handle()
    {
        $this->safeNotify(
            $this->user,
            new NewSupportTicketNotification($this->thread, $this->client)
        );
    }
}
