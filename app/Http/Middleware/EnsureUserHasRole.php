<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage in routes: ->middleware('role:administrator,cswd_personnel')
 *
 * Must run AFTER 'auth:sanctum' in the middleware chain, since it relies on
 * $request->user() already being resolved.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role?->name, $roles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to perform this action.',
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}
