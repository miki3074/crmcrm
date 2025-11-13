<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public static function sendMessage($chatId, $text)
    {
        if (!$chatId) {
            return false; // Нет chat_id — ничего не делаем
        }

        try {
            $token = config('services.telegram.bot_token');

            if (!$token) {
                Log::warning("Telegram bot token отсутствует");
                return false;
            }

            $url = "https://api.telegram.org/bot{$token}/sendMessage";

            $response = Http::timeout(5)->post($url, [
                'chat_id' => $chatId,
                'text'    => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            // Если ошибка Telegram — логируем, но НЕ выбрасываем исключение
            if ($response->failed()) {
                Log::error("Telegram API error: " . $response->body());
                return false;
            }

            return true;

        } catch (\Throwable $e) {
            Log::error("Telegram sendMessage exception: " . $e->getMessage());
            return false;
        }
    }
}
