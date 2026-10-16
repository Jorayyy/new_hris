<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            auth()->logout();

            return redirect()->route('login')->withErrors(['email' => 'Your account is inactive.']);
        }

        if ($roles !== [] && ! $user->hasRole($roles)) {
            abort(403, 'You do not have access to this area.');
        }

        return $next($request);
    }
}
