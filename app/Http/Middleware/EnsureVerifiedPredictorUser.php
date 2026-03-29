<?php

namespace App\Http\Middleware;

use App\Auth\JwtUser;
use App\Exceptions\ApiException;
use App\Services\AuthBoxClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVerifiedPredictorUser
{
    public function __construct(private readonly AuthBoxClient $authBoxClient)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        /** @var JwtUser|null $user */
        $user = $request->user();

        if (! $user instanceof JwtUser) {
            throw ApiException::unauthorized('Authenticated user context is missing.', 'AUTH_USER_MISSING');
        }

        $profile = $this->authBoxClient->currentUserProfile($user->token(), $user->userId());

        if (! $profile->verified) {
            throw ApiException::forbidden(
                'Verified TNBO Sports account required to participate.',
                'PREDICTOR_VERIFICATION_REQUIRED'
            );
        }

        $request->attributes->set('current_user_profile', $profile);

        return $next($request);
    }
}
