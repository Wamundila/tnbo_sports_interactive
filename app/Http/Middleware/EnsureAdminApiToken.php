<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Models\AdminApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! is_string($token) || $token === '') {
            throw ApiException::unauthorized('Admin authentication is required.', 'ADMIN_AUTH_REQUIRED');
        }

        $accessToken = AdminApiToken::query()
            ->with('admin')
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (! $accessToken || ! $accessToken->isActive() || ! $accessToken->admin || $accessToken->admin->status !== 'active') {
            throw ApiException::unauthorized('Admin authentication is required.', 'ADMIN_AUTH_REQUIRED');
        }

        $accessToken->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('current_admin', $accessToken->admin);
        $request->attributes->set('current_admin_token', $accessToken);
        $request->setUserResolver(static fn () => $accessToken->admin);

        return $next($request);
    }
}
