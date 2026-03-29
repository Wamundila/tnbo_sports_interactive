<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogProtectedApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $response = $next($request);
        $duration = (microtime(true) - $startedAt) * 1000;

        Log::info('protected_api_request', [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => (int) round($duration),
            'user_id' => $request->user()?->getAuthIdentifier(),
        ]);

        return $response;
    }
}
