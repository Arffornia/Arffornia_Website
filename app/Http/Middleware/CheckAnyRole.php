<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAnyRole
{
    /**
     * Handle an incoming request and check if the user has any of the required roles.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user || !$user->hasAnyRole($roles)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            abort(403);
        }

        return $next($request);
    }
}
