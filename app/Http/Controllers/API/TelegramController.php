<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->all();
        Log::info('Telegram webhook', $data);

        if (!isset($data['message'])) {
            return response()->json(['ok' => true]);
        }

        $message = $data['message'];
        $chatId  = $message['chat']['id'];
        $text    = trim($message['text'] ?? '');

       
        if (str_starts_with($text, '/start')) {
            $parts = explode(' ', $text);
            $token = $parts[1] ?? null;

            if (!$token) {
                $this->sendMessage($chatId, "❌ Ошибка: отсутствует токен.\nЗайдите в личный кабинет и сгенерируйте токен привязки Telegram.");
                return response()->json(['ok' => true]);
            }

            $user = User::where('telegram_token', $token)->first();

            if ($user) {
                $user->telegram_chat_id = $chatId;
                $user->telegram_token = null; 
                $user->save();

                $this->sendMessage($chatId, "✅ Telegram успешно привязан к вашему аккаунту, {$user->name}!");
            } else {
                $this->sendMessage($chatId, "❌ Неверный токен. Проверьте в личном кабинете.");
            }
        }

        return response()->json(['ok' => true]);
    }

    private function sendMessage($chatId, $text)
    {
        $url = "https://api.telegram.org/bot" . env('TELEGRAM_BOT_TOKEN') . "/sendMessage";

        $client = new \GuzzleHttp\Client();
        $client->post($url, [
            'form_params' => [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ],
        ]);
    }
}
