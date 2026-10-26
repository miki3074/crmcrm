<?php

namespace App\Jobs;

use App\Notify\Channels\MailChannel;
use App\Notify\Channels\TelegramChannel;
use App\Notify\Channels\WebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Доставка уведомления во внешние каналы (push, Telegram, почта).
 * Сбой одного канала не мешает остальным.
 */
class DeliverNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const CHANNELS = [
        'push' => WebPushChannel::class,
        'telegram' => TelegramChannel::class,
        'mail' => MailChannel::class,
    ];

    /**
     * @param array $payload type, category, title, message, link, actor_id
     * @param array<string, int[]> $recipients канал => id получателей
     */
    public function __construct(
        public array $payload,
        public array $recipients
    ) {
    }

    public function handle(): void
    {
        foreach ($this->recipients as $channel => $userIds) {
            $handler = self::CHANNELS[$channel] ?? null;

            if (!$handler || empty($userIds)) {
                continue;
            }

            try {
                $handler::send($userIds, $this->payload);
            } catch (Throwable $e) {
                Log::error("Уведомление не доставлено в канал {$channel}", [
                    'type' => $this->payload['type'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
