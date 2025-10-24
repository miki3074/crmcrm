<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    /**
     * Разрешить доступ только пользователю с нужным email.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->email !== 'miki23074@gmail.com') {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        return $next($request);
    }
}
