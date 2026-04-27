<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class RefreshSanctumTokenExpiration
{
    public function __invoke(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        if ($user === null) {
            return $response;
        }

        $token = $user->currentAccessToken();
        if (! $token instanceof PersonalAccessToken || ! $token->exists) {
            return $response;
        }

        $minutes = (int) config('sanctum.token_inactivity_ttl_minutes', 1440);
        if ($minutes < 1) {
            return $response;
        }

        $token->forceFill([
            'expires_at' => now()->addMinutes($minutes),
        ])->save();

        return $response;
    }
}
