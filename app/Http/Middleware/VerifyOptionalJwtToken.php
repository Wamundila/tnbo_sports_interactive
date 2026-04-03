<?php

namespace App\Http\Middleware;

use App\Auth\JwtUser;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerifyOptionalJwtToken
{
    public function __construct(private readonly JwtService $jwtService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! is_string($token) || $token === '') {
            return $next($request);
        }

        $claims = $this->jwtService->decode($token);
        $user = new JwtUser($claims['sub'], $claims, $token);

        Auth::setUser($user);
        $request->setUserResolver(static fn (): JwtUser => $user);
        $request->attributes->set('jwt_claims', $claims);
        $request->attributes->set('auth_token', $token);

        return $next($request);
    }
}
