<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}


// <?php

// namespace App\Http\Controllers\Auth;

// use App\Http\Controllers\Controller;
// use App\Http\Requests\Auth\LoginRequest;
// use App\Models\User;
// use App\Notifications\LoginCodeNotification;
// use Illuminate\Http\RedirectResponse;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Hash;
// use Illuminate\Support\Facades\Route;
// use Illuminate\Validation\ValidationException;
// use Inertia\Inertia;
// use Inertia\Response;
// use Illuminate\Support\Facades\RateLimiter;
// use App\Jobs\SendLoginCodeNotification;

// class AuthenticatedSessionController extends Controller
// {
//     /**
//      * Страница логина.
//      */
//     public function create(): Response
//     {
//         return Inertia::render('Auth/Login', [
//             'canResetPassword' => Route::has('password.request'),
//             'status' => session('status'),
//         ]);
//     }

//     /**
//      * Первый этап:
//      * email + пароль.
//      */
//     public function store(LoginRequest $request): RedirectResponse
//     {
//         /*
//          * LoginRequest::authenticate()
//          * обычно уже авторизует пользователя.
//          */
//         $request->authenticate();

//         $user = Auth::user();

//         if (!$user) {
//             throw ValidationException::withMessages([
//                 'email' => 'Не удалось выполнить вход.',
//             ]);
//         }

//         /*
//          * Генерируем 6-значный код.
//          */
//         $code = (string) random_int(
//             100000,
//             999999
//         );

//         /*
//          * Храним только хэш.
//          */
//         $user->forceFill([
//             'login_code_hash' =>
//                 Hash::make($code),

//             'login_code_expires_at' =>
//                 now()->addMinutes(10),
//         ])->save();

//         /*
//          * Сохраняем ID пользователя
//          * во временной сессии.
//          */
//         $request->session()->put(
//             'login_2fa_user_id',
//             $user->id
//         );

//         /*
//          * Запоминаем remember.
//          */
//         $request->session()->put(
//             'login_2fa_remember',
//             $request->boolean('remember')
//         );

//         /*
//          * ВАЖНО:
//          * убираем обычную авторизацию,
//          * пока пользователь не ввёл код.
//          */
//         Auth::guard('web')->logout();

//         /*
//          * Отправляем код.
//          */
//        SendLoginCodeNotification::dispatch(
//     $user->id,
//     $code
// );

//         return redirect()
//             ->route('login.verify');
//     }

//     /**
//      * Страница ввода кода.
//      */
//     public function createVerify(
//         Request $request
//     ): Response|RedirectResponse {
//         if (
//             !$request->session()->has(
//                 'login_2fa_user_id'
//             )
//         ) {
//             return redirect()
//                 ->route('login');
//         }

//         return Inertia::render(
//             'Auth/LoginVerify',
//             [
//                 'status' =>
//                     session('status'),
//             ]
//         );
//     }

//     /**
//      * Проверка 6-значного кода.
//      */
//     public function verify(Request $request): RedirectResponse
// {
//     $validated = $request->validate([
//         'code' => [
//             'required',
//             'digits:6',
//         ],
//     ]);

//     $userId = $request
//         ->session()
//         ->get('login_2fa_user_id');

//     if (!$userId) {
//         return redirect()
//             ->route('login');
//     }

//     /*
//      * Ключ ограничения.
//      *
//      * Он уникален для:
//      * пользователь + IP
//      */
//     $rateLimitKey =
//         'login-2fa:'
//         . $userId
//         . ':'
//         . $request->ip();

//     /*
//      * Разрешаем максимум 5 неправильных попыток.
//      */
//     if (
//         RateLimiter::tooManyAttempts(
//             $rateLimitKey,
//             5
//         )
//     ) {
//         /*
//          * Сколько секунд осталось до разблокировки.
//          */
//         $seconds = RateLimiter::availableIn(
//             $rateLimitKey
//         );

//         throw ValidationException::withMessages([
//             'code' =>
//                 "Слишком много попыток. Повторите через {$seconds} сек.",
//         ]);
//     }

//     $user = User::find($userId);

//     if (!$user) {
//         $this->clearTwoFactorSession(
//             $request
//         );

//         return redirect()
//             ->route('login');
//     }

//     /*
//      * Проверяем срок действия кода.
//      */
//     if (
//         !$user->login_code_expires_at
//         ||
//         now()->greaterThan(
//             $user->login_code_expires_at
//         )
//     ) {
//         throw ValidationException::withMessages([
//             'code' =>
//                 'Срок действия кода истёк. Запросите новый код.',
//         ]);
//     }

//     /*
//      * Проверяем код.
//      */
//     if (
//         !$user->login_code_hash
//         ||
//         !Hash::check(
//             $validated['code'],
//             $user->login_code_hash
//         )
//     ) {
//         /*
//          * Регистрируем неправильную попытку.
//          *
//          * 60 = блокировка на 60 секунд
//          * после достижения лимита.
//          */
//         RateLimiter::hit(
//             $rateLimitKey,
//             60
//         );

//         /*
//          * Можно показать количество оставшихся попыток.
//          */
//         $remaining = RateLimiter::remaining(
//             $rateLimitKey,
//             5
//         );

//         throw ValidationException::withMessages([
//             'code' =>
//                 "Неверный код подтверждения. Осталось попыток: {$remaining}.",
//         ]);
//     }

//     /*
//      * Код правильный.
//      *
//      * Полностью очищаем счётчик неправильных попыток.
//      */
//     RateLimiter::clear(
//         $rateLimitKey
//     );

//     $remember = (bool) $request
//         ->session()
//         ->get(
//             'login_2fa_remember',
//             false
//         );

//     /*
//      * Теперь окончательно авторизуем.
//      */
//     Auth::login(
//         $user,
//         $remember
//     );

//     /*
//      * Одноразовый код больше не нужен.
//      */
//     $user->forceFill([
//         'login_code_hash' => null,
//         'login_code_expires_at' => null,
//     ])->save();

//     /*
//      * Удаляем временные данные 2FA.
//      */
//     $this->clearTwoFactorSession(
//         $request
//     );

//     /*
//      * Защита сессии.
//      */
//     $request
//         ->session()
//         ->regenerate();

//     return redirect()
//         ->intended(
//             route('dashboard')
//         );
// }

//     /**
//      * Повторная отправка кода.
//      */
//     public function resend(
//         Request $request
//     ): RedirectResponse {
//         $userId = $request
//             ->session()
//             ->get('login_2fa_user_id');

//         if (!$userId) {
//             return redirect()
//                 ->route('login');
//         }

//         $user = User::find($userId);

//         if (!$user) {
//             $this->clearTwoFactorSession(
//                 $request
//             );

//             return redirect()
//                 ->route('login');
//         }

//         $code = (string) random_int(
//             100000,
//             999999
//         );

//         $user->forceFill([
//             'login_code_hash' =>
//                 Hash::make($code),

//             'login_code_expires_at' =>
//                 now()->addMinutes(10),
//         ])->save();

//        SendLoginCodeNotification::dispatch(
//     $user->id,
//     $code
// );
        

//         return back()->with(
//             'status',
//             'Новый код отправлен на вашу почту.'
//         );
//     }

//     /**
//      * Выход.
//      */
//     public function destroy(
//         Request $request
//     ): RedirectResponse {
//         Auth::guard('web')->logout();

//         $request
//             ->session()
//             ->invalidate();

//         $request
//             ->session()
//             ->regenerateToken();

//         return redirect('/');
//     }

//     private function clearTwoFactorSession(
//         Request $request
//     ): void {
//         $request->session()->forget([
//             'login_2fa_user_id',
//             'login_2fa_remember',
//         ]);
//     }
// }