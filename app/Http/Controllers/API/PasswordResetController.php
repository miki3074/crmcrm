<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    // Отправка ссылки для сброса пароля на email
    public function sendResetLinkViaEmail(Request $request)
    {
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
                'message' => 'Пользователь не найден.'
            ], 404);
        }

        // Создаем токен сброса пароля
        $token = Password::createToken($user);

        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ], false));

        // Отправляем письмо
        try {
            $this->sendResetEmail($user, $resetUrl);

            return response()->json([
                'success' => true,
                'message' => 'Ссылка для сброса пароля отправлена на ваш email ✅'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отправке письма: ' . $e->getMessage()
            ], 500);
        }
    }

    // Сброс пароля
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed'
        ], [
            'password.required' => 'Введите новый пароль',
            'password.min' => 'Пароль должен содержать минимум 6 символов',
            'password.confirmed' => 'Пароли не совпадают'
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Пароль успешно изменен'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Неверный токен или email'
        ], 422);
    }

    // Отправка email со ссылкой для сброса пароля
    private function sendResetEmail($user, $resetUrl)
    {
        $subject = 'Восстановление пароля';

        Mail::send('emails.password-reset', [
            'user' => $user,
            'resetUrl' => $resetUrl
        ], function ($message) use ($user, $subject) {
            $message->to($user->email)
                ->from(config('mail.from.address'), config('mail.from.name'))
                ->subject($subject);
        });
    }
}
