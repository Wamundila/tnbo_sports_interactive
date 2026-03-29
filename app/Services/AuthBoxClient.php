<?php

namespace App\Services;

use App\Data\AuthBoxUserProfile;
use App\Exceptions\ApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthBoxClient
{
    public function currentUserProfile(string $token, string $expectedUserId): AuthBoxUserProfile
    {
        $ttl = (int) config('services.authbox.profile_cache_ttl_seconds', 60);
        $cacheKey = sprintf('authbox-profile:%s:%s', $expectedUserId, sha1($token));

        $payload = Cache::remember($cacheKey, $ttl, function () use ($token, $expectedUserId) {
            $baseUrl = config('services.authbox.base_url');
            $path = config('services.authbox.current_user_path', '/api/v1/me');
            $apiKey = config('services.authbox.api_key');

            if (! is_string($baseUrl) || trim($baseUrl) === '') {
                throw ApiException::badGateway('AuthBox base URL is not configured.', 'AUTHBOX_CONFIGURATION_ERROR');
            }

            if (! is_string($apiKey) || trim($apiKey) === '') {
                throw ApiException::badGateway('AuthBox API key is not configured.', 'AUTHBOX_CONFIGURATION_ERROR');
            }

            try {
                $response = Http::baseUrl($baseUrl)
                    ->timeout((int) config('services.authbox.timeout_seconds', 5))
                    ->acceptJson()
                    ->withHeaders([
                        'X-API-Key' => $apiKey,
                    ])
                    ->withToken($token)
                    ->get($path);
            } catch (ConnectionException $exception) {
                Log::warning('authbox_profile_connection_failed', [
                    'user_id' => $expectedUserId,
                    'base_url' => $baseUrl,
                    'path' => $path,
                    'message' => $exception->getMessage(),
                ]);

                throw ApiException::badGateway(
                    'AuthBox profile lookup failed.',
                    'AUTHBOX_PROFILE_UNAVAILABLE'
                );
            }

            if (! $response->successful()) {
                Log::warning('authbox_profile_lookup_failed', [
                    'user_id' => $expectedUserId,
                    'base_url' => $baseUrl,
                    'path' => $path,
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);

                throw ApiException::badGateway(
                    'AuthBox profile lookup failed.',
                    'AUTHBOX_PROFILE_UNAVAILABLE',
                    ['status' => $response->status()]
                );
            }

            $json = $response->json();

            if (! is_array($json)) {
                Log::warning('authbox_profile_invalid_payload', [
                    'user_id' => $expectedUserId,
                    'base_url' => $baseUrl,
                    'path' => $path,
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);

                throw ApiException::badGateway(
                    'AuthBox profile response was invalid.',
                    'AUTHBOX_PROFILE_INVALID'
                );
            }

            return $json;
        });

        return AuthBoxUserProfile::fromResponse($payload, $expectedUserId);
    }
}
