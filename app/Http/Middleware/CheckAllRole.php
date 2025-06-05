<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAllRole
{
    /**
     * Handle an incoming request and check if the user has all required roles.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user || !$user->hasAllRoles($roles)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            abort(403);
        }

        return $next($request);
    }
}
