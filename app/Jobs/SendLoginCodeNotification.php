<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\LoginCodeNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendLoginCodeNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $userId,
        protected string $code
    ) {
    }

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (!$user || !$user->email) {
            return;
        }

        try {
            $user->notify(
                new LoginCodeNotification(
                    $this->code
                )
            );
        } catch (\Throwable $e) {
            Log::error(
                "Ошибка отправки кода входа пользователю {$this->userId}: "
                . $e->getMessage()
            );

            throw $e;
        }
    }
}