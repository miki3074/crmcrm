<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;


class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }


public function render($request, Throwable $exception)
{
    // 1️⃣ Если пользователь неавторизован — на страницу логина
    if ($exception instanceof AuthenticationException) {
        return redirect()->guest(route('login'));
    }

    // 2️⃣ Если нет прав доступа — на dashboard
    if ($exception instanceof AuthorizationException) {
        // проверяем, обычный это браузер или API
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Нет доступа.'], 403);
        }

        // для обычных страниц (Inertia или Blade)
        return redirect('/dashboard')->with('error', 'У вас нет доступа к этой странице.');
    }

    // 3️⃣ Для остальных HTTP-исключений
    if ($exception instanceof HttpExceptionInterface) {
        if ($exception->getStatusCode() === 403) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Нет доступа.'], 403);
            }
            return redirect('/dashboard')->with('error', 'Нет доступа.');
        }
    }

    // 4️⃣ Всё остальное — по стандарту
    return parent::render($request, $exception);
}

}
