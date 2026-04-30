<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EmailVerificationController extends Controller
{
    // Отправка кода подтверждения
    public function sendVerificationCode(Request $request)
    {
        try {
            Log::info('Email verification request received', ['email' => $request->email]);

            $request->validate([
                'email' => 'required|email|exists:users,email'
            ], [
                'email.required' => 'Введите email адрес',
                'email.email' => 'Введите корректный email адрес',
                'email.exists' => 'Пользователь с таким email не найден'
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Пользователь не найден'
                ], 404);
            }

            // Проверяем, не подтвержден ли уже email
            if ($user->email_verified_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email уже подтвержден'
                ], 422);
            }

            // Генерируем 6-значный код
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Сохраняем код в кэш на 10 минут (используем email как ключ)
            Cache::put('email_verification_' . $user->email, $code, now()->addMinutes(10));

            // Отправляем письмо с кодом
            try {
                $this->sendVerificationEmail($user, $code);

                Log::info("Verification code sent to user {$user->id} ({$user->email})");

                return response()->json([
                    'success' => true,
                    'message' => 'Код подтверждения отправлен на email'
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to send verification email: " . $e->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при отправке письма. Проверьте настройки почты.'
                ], 500);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first()
            ], 422);
        } catch (\Exception $e) {
            Log::error("Unexpected error in sendVerificationCode: " . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка на сервере. Пожалуйста, попробуйте позже.'
            ], 500);
        }
    }

    // Проверка кода подтверждения
    public function verifyCode(Request $request)
    {
        try {
            Log::info('Email verification attempt', ['email' => $request->email]);

            $request->validate([
                'email' => 'required|email|exists:users,email',
                'code' => 'required|string|size:6',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Пользователь не найден'
                ], 404);
            }

            // Проверяем, не подтвержден ли уже email
            if ($user->email_verified_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email уже подтвержден'
                ], 422);
            }

            // Получаем код из кэша
            $cachedCode = Cache::get('email_verification_' . $user->email);

            if (!$cachedCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Код подтверждения истек. Запросите новый код.'
                ], 422);
            }

            if ($cachedCode !== $request->code) {
                Log::warning("Invalid verification code for user {$user->id}");

                return response()->json([
                    'success' => false,
                    'message' => 'Неверный код подтверждения'
                ], 422);
            }

            // Подтверждаем email
            $user->email_verified_at = Carbon::now();
            $user->save();

            Log::info("User {$user->id} verified email successfully");

            // Удаляем код из кэша
            Cache::forget('email_verification_' . $user->email);

            return response()->json([
                'success' => true,
                'message' => 'Email успешно подтвержден'
            ]);

        } catch (\Exception $e) {
            Log::error("Error in verifyCode: " . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка на сервере. Пожалуйста, попробуйте позже.'
            ], 500);
        }
    }

    // Отправка email с кодом
    private function sendVerificationEmail($user, $code)
    {
        $subject = 'Подтверждение email адреса';

        // Простой текст письма
        $html = "
            <html>
            <head>
                <meta charset='UTF-8'>
                <title>Подтверждение email</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .code { background: #f0f9ff; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0; }
                    .code-number { font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #0284c7; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h2>Подтверждение email адреса</h2>
                    <p>Здравствуйте, <strong>{$user->name}</strong>!</p>
                    <p>Ваш код подтверждения:</p>
                    <div class='code'>
                        <div class='code-number'>{$code}</div>
                    </div>
                    <p>Код действителен в течение <strong>10 минут</strong>.</p>
                    <p>Если вы не запрашивали подтверждение email, просто проигнорируйте это письмо.</p>
                    <hr>
                    <p>С уважением, команда системы управления проектами</p>
                </div>
            </body>
            </html>
        ";

        Mail::send([], [], function ($message) use ($user, $subject, $html) {
            $message->to($user->email)
                ->from(config('mail.from.address'), config('mail.from.name'))
                ->subject($subject)
                ->html($html);
        });
    }

    // Проверка статуса верификации
    public function checkVerificationStatus(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'verified' => false,
                    'message' => 'Пользователь не авторизован'
                ], 401);
            }

            return response()->json([
                'verified' => !is_null($user->email_verified_at),
                'email' => $user->email,
                'verified_at' => $user->email_verified_at
            ]);
        } catch (\Exception $e) {
            Log::error("Error in checkVerificationStatus: " . $e->getMessage());

            return response()->json([
                'verified' => false,
                'message' => 'Ошибка проверки статуса'
            ], 500);
        }
    }
}
