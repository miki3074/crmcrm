<?php

namespace App\Notify\Channels;

use App\Models\User;
use App\Services\TelegramService;

class TelegramChannel
{
    public static function send(array $userIds, array $payload): void
    {
        $users = User::whereIn('id', $userIds)
            ->whereNotNull('telegram_chat_id')
            ->get(['id', 'telegram_chat_id']);

        if ($users->isEmpty()) {
            return;
        }

        $text = '';

        if (!empty($payload['title'])) {
            $text .= '<b>' . e($payload['title']) . "</b>\n";
        }

        $text .= e($payload['message']);

        if (!empty($payload['link'])) {
            $text .= "\n<a href=\"" . e(url($payload['link'])) . '">Открыть</a>';
        }

        foreach ($users as $user) {
            TelegramService::sendMessage($user->telegram_chat_id, $text);
        }
    }
}
