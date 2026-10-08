<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('user.type:admin') or 'user.type:talent,client'.
 * Must run after auth:api.
 */
class EnsureUserType
{
    public function handle(Request $request, Closure $next, string ...$types): Response
    {
        $user = auth('api')->user();

        if (! $user || ! in_array($user->type, $types, true)) {
            return response()->json([
                'message' => 'You do not have access to this resource.',
            ], 403);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'This account is not active.',
            ], 403);
        }

        return $next($request);
    }
}
