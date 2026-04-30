<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Send verification code to email
     */
    public function sendVerificationCode(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'phone' => 'nullable|string|max:25',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Генерируем 6-значный код
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Сохраняем данные пользователя в кэш на 15 минут
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'code' => $code,
        ];

        Cache::put('registration_' . $request->email, $userData, now()->addMinutes(15));

        // Отправляем код на email
        try {
            $this->sendVerificationEmail($request->email, $request->name, $code);

            return response()->json([
                'success' => true,
                'message' => 'Код подтверждения отправлен на email',
                'email' => $request->email
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отправке письма: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify code and create user
     */
    public function verifyAndRegister(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        // Получаем данные из кэша
        $userData = Cache::get('registration_' . $request->email);

        if (!$userData) {
            return response()->json([
                'success' => false,
                'message' => 'Время подтверждения истекло. Пожалуйста, начните регистрацию заново.'
            ], 422);
        }

        // Проверяем код
        if ($userData['code'] !== $request->code) {
            return response()->json([
                'success' => false,
                'message' => 'Неверный код подтверждения'
            ], 422);
        }

        // Проверяем, не зарегистрирован ли уже пользователь
        if (User::where('email', $request->email)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Пользователь с таким email уже зарегистрирован'
            ], 422);
        }

        // Создаем пользователя
        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'phone' => $userData['phone'],
            'password' => $userData['password'],
            'email_verified_at' => now(),
        ]);

        $user->assignRole('admin');

        // Удаляем данные из кэша
        Cache::forget('registration_' . $request->email);

        // Автоматически авторизуем пользователя
        Auth::login($user);

        event(new Registered($user));

        return response()->json([
            'success' => true,
            'message' => 'Регистрация успешно завершена',
            'redirect' => RouteServiceProvider::HOME
        ]);
    }

    /**
     * Send verification email
     */
    private function sendVerificationEmail($email, $name, $code)
    {
        $subject = 'Подтверждение регистрации';

        Mail::send('emails.verification-code', [
            'name' => $name,
            'code' => $code,
            'email' => $email
        ], function ($message) use ($email, $subject) {
            $message->to($email)
                ->from(config('mail.from.address'), config('mail.from.name'))
                ->subject($subject);
        });
    }
}
