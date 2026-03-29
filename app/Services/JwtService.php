<?php

namespace App\Services;

use App\Exceptions\ApiException;

class JwtService
{
    public function decode(string $token): array
    {
        [$encodedHeader, $encodedPayload, $encodedSignature] = $this->splitToken($token);

        $header = $this->decodeSegment($encodedHeader);
        $payload = $this->decodeSegment($encodedPayload);
        $signature = $this->base64UrlDecode($encodedSignature);

        $algorithm = config('jwt.algorithm', 'RS256');

        if (($header['alg'] ?? null) !== $algorithm) {
            throw ApiException::unauthorized('Unsupported JWT algorithm.', 'AUTH_TOKEN_INVALID');
        }

        $this->verifySignature(
            data: $encodedHeader.'.'.$encodedPayload,
            signature: $signature,
            algorithm: $algorithm,
        );

        $this->validateClaims($payload);

        return $payload;
    }

    private function splitToken(string $token): array
    {
        $segments = explode('.', $token);

        if (count($segments) !== 3) {
            throw ApiException::unauthorized('Malformed JWT received.', 'AUTH_TOKEN_INVALID');
        }

        return $segments;
    }

    private function decodeSegment(string $segment): array
    {
        $decoded = json_decode($this->base64UrlDecode($segment), true);

        if (! is_array($decoded)) {
            throw ApiException::unauthorized('JWT payload could not be decoded.', 'AUTH_TOKEN_INVALID');
        }

        return $decoded;
    }

    private function verifySignature(string $data, string $signature, string $algorithm): void
    {
        $publicKey = $this->resolvePublicKey();
        $opensslAlgorithm = match ($algorithm) {
            'RS256' => OPENSSL_ALGO_SHA256,
            default => throw ApiException::unauthorized('Unsupported JWT algorithm.', 'AUTH_TOKEN_INVALID'),
        };

        $verified = openssl_verify($data, $signature, $publicKey, $opensslAlgorithm);

        if ($verified !== 1) {
            throw ApiException::unauthorized('JWT signature verification failed.', 'AUTH_TOKEN_INVALID');
        }
    }

    private function validateClaims(array $payload): void
    {
        $now = time();
        $skew = (int) config('jwt.clock_skew_seconds', 30);
        $subjectPattern = config('jwt.subject_pattern', '/^ts_\d+$/');
        $subject = $payload['sub'] ?? null;

        if (! is_string($subject) || preg_match($subjectPattern, $subject) !== 1) {
            throw ApiException::unauthorized('JWT subject is invalid.', 'AUTH_TOKEN_INVALID');
        }

        if (isset($payload['nbf']) && is_numeric($payload['nbf']) && (int) $payload['nbf'] > $now + $skew) {
            throw ApiException::unauthorized('JWT is not yet valid.', 'AUTH_TOKEN_INVALID');
        }

        if (isset($payload['exp']) && is_numeric($payload['exp']) && (int) $payload['exp'] < $now - $skew) {
            throw ApiException::unauthorized('JWT has expired.', 'AUTH_TOKEN_EXPIRED');
        }

        $issuer = config('jwt.issuer');

        if (is_string($issuer) && $issuer !== '' && ($payload['iss'] ?? null) !== $issuer) {
            throw ApiException::unauthorized('JWT issuer is invalid.', 'AUTH_TOKEN_INVALID');
        }

        $audience = config('jwt.audience');

        if (is_string($audience) && $audience !== '') {
            $tokenAudience = $payload['aud'] ?? null;
            $audiences = is_array($tokenAudience) ? $tokenAudience : [$tokenAudience];

            if (! in_array($audience, $audiences, true)) {
                throw ApiException::unauthorized('JWT audience is invalid.', 'AUTH_TOKEN_INVALID');
            }
        }
    }

    private function resolvePublicKey(): string
    {
        $inlineKey = config('jwt.public_key');

        if (is_string($inlineKey) && trim($inlineKey) !== '') {
            return $inlineKey;
        }

        $path = $this->resolvePublicKeyPath(config('jwt.public_key_path'));

        if (! is_string($path) || ! is_file($path)) {
            throw ApiException::badGateway('JWT public key is not configured.', 'AUTH_CONFIGURATION_ERROR');
        }

        $contents = file_get_contents($path);

        if (! is_string($contents) || trim($contents) === '') {
            throw ApiException::badGateway('JWT public key is empty.', 'AUTH_CONFIGURATION_ERROR');
        }

        return $contents;
    }

    private function resolvePublicKeyPath(mixed $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $trimmedPath = trim($path);

        if ($this->isAbsolutePath($trimmedPath)) {
            return $trimmedPath;
        }

        return base_path($trimmedPath);
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('~^[A-Za-z]:[\\/]~', $path) === 1;
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;

        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        if ($decoded === false) {
            throw ApiException::unauthorized('JWT segment is invalid base64.', 'AUTH_TOKEN_INVALID');
        }

        return $decoded;
    }
}
