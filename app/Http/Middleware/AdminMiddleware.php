<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->isAdmin()) {
            return $next($request);
        }
        if (auth()->check() && auth()->user()->isUser()) {
            return redirect()->route('user.dashboard');
        }
        abort(403, 'Unauthorized.');
    }
}
