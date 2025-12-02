<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SupportOnly
{
    public function handle($request, Closure $next)
    {
        if (!auth()->check() || !auth()->user()->hasRole('support')) {
            return response()->json(['message' => 'Недостаточно прав'], 403);
        }

        return $next($request);
    }
}
