<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInternalServiceRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = config('services.interactive.service_key');

        if (is_string($configuredKey) && trim($configuredKey) !== '') {
            $providedKey = $request->header('X-TNBO-Service-Key');

            if (! is_string($providedKey) || ! hash_equals($configuredKey, $providedKey)) {
                throw ApiException::forbidden(
                    'Interactive requests must include a valid internal service key.',
                    'SERVICE_UNAUTHORIZED'
                );
            }
        }

        return $next($request);
    }
}
