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
        $request->validate([
            'email' => 'required|email|exists:users,email',
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

        // Сохраняем код в кэш на 10 минут
        Cache::put('email_verification_' . $user->id, $code, now()->addMinutes(10));

        // Также сохраняем в БД для надежности
        $user->email_verification_code = $code;
        $user->email_verification_code_expires_at = now()->addMinutes(10);
        $user->save();

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
                'message' => 'Ошибка при отправке письма: ' . $e->getMessage()
            ], 500);
        }
    }

    // Проверка кода подтверждения
    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
        ]);

        Log::info("Verification attempt for email: {$request->email}");

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

        // Проверяем код из кэша
        $cachedCode = Cache::get('email_verification_' . $user->id);

        // Проверяем код из БД
        $dbCode = $user->email_verification_code;
        $dbExpires = $user->email_verification_code_expires_at;

        $isValid = false;

        // Проверяем сначала кэш, потом БД
        if ($cachedCode && $cachedCode === $request->code) {
            $isValid = true;
        } elseif ($dbCode && $dbCode === $request->code && $dbExpires && now()->lessThan($dbExpires)) {
            $isValid = true;
        }

        if (!$isValid) {
            Log::warning("Invalid verification code for user {$user->id}. Expected: {$cachedCode}, got: {$request->code}");

            return response()->json([
                'success' => false,
                'message' => 'Неверный или просроченный код подтверждения'
            ], 422);
        }

        // Подтверждаем email
        $user->email_verified_at = Carbon::now();
        $user->email_verification_code = null;
        $user->email_verification_code_expires_at = null;
        $user->save();

        Log::info("User {$user->id} verified email successfully at {$user->email_verified_at}");

        // Удаляем код из кэша
        Cache::forget('email_verification_' . $user->id);

        return response()->json([
            'success' => true,
            'message' => 'Email успешно подтвержден',
            'verified_at' => $user->email_verified_at
        ]);
    }

    // Отправка email с кодом
    private function sendVerificationEmail($user, $code)
    {
        $subject = 'Подтверждение email адреса';

        Mail::send('emails.verification', [
            'user' => $user,
            'code' => $code
        ], function ($message) use ($user, $subject) {
            $message->to($user->email)
                ->from(config('mail.from.address'), config('mail.from.name'))
                ->subject($subject);
        });
    }

    // Проверка статуса верификации
    public function checkVerificationStatus(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'verified' => !is_null($user->email_verified_at),
            'email' => $user->email,
            'verified_at' => $user->email_verified_at
        ]);
    }
}
