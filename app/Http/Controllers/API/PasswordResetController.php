<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\Models\User;
use GuzzleHttp\Client;

class PasswordResetController extends Controller
{
    

public function sendResetLinkViaTelegram(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Пользователь не найден.'
            ], 404);
        }

        if (!$user->telegram_chat_id) {
            return response()->json([
                'success' => false,
                'message' => 'К аккаунту не привязан Telegram.'
            ], 400);
        }

        // Создаем токен сброса пароля
        $token = Password::createToken($user);

        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ], false));

        // Отправляем ссылку в Telegram
        $this->sendMessage($user->telegram_chat_id, "🔑 Для сброса пароля перейдите по ссылке:\n\n{$resetUrl}");

        return response()->json([
            'success' => true,
            'message' => 'Ссылка на сброс пароля отправлена в Telegram ✅'
        ]);
    }

    /**
     * Утилита для отправки сообщения в Telegram
     */
    private function sendMessage($chatId, $text)
    {
        $url = "https://api.telegram.org/bot" . env('TELEGRAM_BOT_TOKEN') . "/sendMessage";

        $client = new Client();
        $client->post($url, [
            'form_params' => [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ],
        ]);
    }

}
