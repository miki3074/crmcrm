<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

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
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
   public function store(Request $request): RedirectResponse
{
    $messages = [
        'name.required' => 'Введите имя.',
        'email.required' => 'Введите email.',
        'email.email' => 'Введите корректный email.',
        'email.unique' => 'Этот email уже зарегистрирован.',
        'password.required' => 'Введите пароль.',
        'password.confirmed' => 'Пароли не совпадают.',
        'password.min' => 'Пароль должен содержать не менее :min символов.',
    ];

    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
        'password' => ['required', 'confirmed', Rules\Password::defaults()],
    ], $messages);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    $user->assignRole('admin');

    event(new Registered($user));

    Auth::login($user);

    return redirect(RouteServiceProvider::HOME);
}

}
