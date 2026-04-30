<?php

namespace App\Jobs;

use App\Models\SupportThread;
use App\Models\User;
use App\Notifications\NewSupportMessageNotification;
use App\Traits\HandlesEmailErrors;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNewSupportMessageNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, HandlesEmailErrors;

    protected $user;
    protected $thread;
    protected $message;
    protected $sender;
    protected $isSupport;

    public function __construct(User $user, SupportThread $thread, $message, User $sender, bool $isSupport)
    {
        $this->user = $user;
        $this->thread = $thread;
        $this->message = $message;
        $this->sender = $sender;
        $this->isSupport = $isSupport;
    }

    public function handle()
    {
        $this->safeNotify(
            $this->user,
            new NewSupportMessageNotification($this->thread, $this->message, $this->sender, $this->isSupport)
        );
    }
}
