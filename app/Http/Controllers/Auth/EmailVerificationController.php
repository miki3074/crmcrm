<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class EmailVerificationController extends Controller
{
    // Страница с сообщением о необходимости подтверждения
    public function notice()
    {
        return Inertia::render('Auth/VerifyEmail');
    }

    // Подтверждение email по ссылке
    public function verify(Request $request, $id, $hash)
    {
        Log::info("Verification attempt for user ID: {$id}");

        $user = User::find($id);

        if (!$user) {
            Log::warning("User not found: {$id}");
            return redirect()->route('login')->with('error', 'Пользователь не найден.');
        }

        Log::info("User found: {$user->email}, is_active: {$user->is_active}, email_verified_at: {$user->email_verified_at}");

        // Проверяем hash
        $expectedHash = sha1($user->email . $user->id);
        $sessionHash = session('email_verification_hash_' . $user->id);

        if ($expectedHash !== $hash && $sessionHash !== $hash) {
            Log::warning("Invalid hash for user {$id}. Expected: {$expectedHash}, Got: {$hash}");
            return redirect()->route('login')->with('error', 'Неверная ссылка подтверждения.');
        }

        // Если пользователь уже активирован
        if ($user->is_active && $user->email_verified_at) {
            Log::info("User {$id} already verified");
            Auth::login($user);
            return redirect()->route('dashboard')->with('success', 'Email уже подтвержден. Добро пожаловать!');
        }

        // Активируем пользователя
        $user->is_active = true;
        $user->email_verified_at = now();
        $user->save();

        Log::info("User {$id} activated successfully");

        // Очищаем hash из сессии
        session()->forget('email_verification_hash_' . $user->id);

        // Автоматически логиним пользователя
        Auth::login($user);

        Log::info("User {$id} logged in after verification");

        return redirect()->route('dashboard')->with('success', 'Email успешно подтвержден! Добро пожаловать в систему.');
    }

    // Повторная отправка ссылки подтверждения
    public function resend(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ], [
            'email.required' => 'Введите email',
            'email.email' => 'Введите корректный email',
            'email.exists' => 'Пользователь с таким email не найден'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->with('error', 'Пользователь не найден.');
        }

        // Если пользователь уже активирован
        if ($user->is_active && $user->email_verified_at) {
            return back()->with('error', 'Пользователь уже активирован. Вы можете войти в систему.');
        }

        // Генерируем новый hash
        $hash = sha1($user->email . $user->id);
        session(['email_verification_hash_' . $user->id => $hash]);

        // Отправляем письмо
        $verificationUrl = route('verification.verify', ['id' => $user->id, 'hash' => $hash]);

        try {
            Mail::send('emails.verification-register', [
                'user' => $user,
                'verificationUrl' => $verificationUrl
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->from(config('mail.from.address'), config('mail.from.name'))
                    ->subject('Подтверждение регистрации');
            });

            return back()->with('status', 'Ссылка для подтверждения отправлена повторно на email ' . $user->email);
        } catch (\Exception $e) {
            Log::error("Failed to send verification email: " . $e->getMessage());
            return back()->with('error', 'Ошибка при отправке письма. Попробуйте позже.');
        }
    }
}
