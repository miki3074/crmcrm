<?php

namespace App\Jobs;

use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTelegramNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Попытки выполнения при ошибке
    public $tries = 3;
    // Пауза между попытками (секунды)
    public $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected string $chatId,
        protected string $text
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        TelegramService::sendMessage($this->chatId, $this->text);
    }
}
