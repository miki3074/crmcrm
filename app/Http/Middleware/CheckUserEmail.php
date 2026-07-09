<?php
// app/Http/Middleware/CheckUserEmail.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckUserEmail
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $permission)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $email = $user->email;
        $allowedCreate = ['dir@npoenergoteh.ru', 'miki23074@gmail.com'];
        $allowedEditDelete = ['miki23074@gmail.com'];

        switch ($permission) {
            case 'create':
                if (!in_array($email, $allowedCreate)) {
                    // 🔥 Редирект на главную с сообщением
                    return redirect('/')->with('error', 'У вас нет прав для создания пользователей');
                }
                break;

            case 'edit':
                if (!in_array($email, $allowedEditDelete)) {
                    return redirect('/')->with('error', 'У вас нет прав для редактирования пользователей');
                }
                break;

            case 'delete':
                if (!in_array($email, $allowedEditDelete)) {
                    return redirect('/')->with('error', 'У вас нет прав для удаления пользователей');
                }
                break;

            case 'view':
                // Просмотр доступен всем авторизованным
                break;

            default:
                return redirect('/')->with('error', 'Неизвестное разрешение');
        }

        return $next($request);
    }
}
