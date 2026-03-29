<?php

namespace App\Data;

use App\Exceptions\ApiException;

readonly class AuthBoxUserProfile
{
    public function __construct(
        public string $userId,
        public ?string $displayName,
        public ?string $avatarUrl,
        public ?string $emailVerifiedAt,
        public bool $verified,
        public array $raw,
    ) {
    }

    public static function fromResponse(array $payload, string $expectedUserId): self
    {
        $userId = self::firstValue($payload, [
            'user_id',
            'id',
            'data.user_id',
            'data.id',
            'user.user_id',
            'user.id',
        ]) ?? $expectedUserId;

        if ($userId !== $expectedUserId) {
            throw ApiException::unauthorized(
                'Authenticated user does not match the resolved AuthBox profile.',
                'AUTHBOX_USER_MISMATCH'
            );
        }

        $emailVerifiedAt = self::stringValue($payload, [
            'email_verified_at',
            'data.email_verified_at',
            'user.email_verified_at',
        ]);

        $verifiedFlag = self::booleanValue($payload, [
            'is_verified',
            'verified',
            'data.is_verified',
            'data.verified',
            'user.is_verified',
            'user.verified',
        ]);

        return new self(
            userId: $userId,
            displayName: self::stringValue($payload, [
                'display_name',
                'name',
                'data.display_name',
                'data.name',
                'user.display_name',
                'user.name',
            ]),
            avatarUrl: self::stringValue($payload, [
                'avatar_url',
                'avatar',
                'data.avatar_url',
                'data.avatar',
                'user.avatar_url',
                'user.avatar',
            ]),
            emailVerifiedAt: $emailVerifiedAt,
            verified: $emailVerifiedAt !== null || $verifiedFlag === true,
            raw: $payload,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'display_name' => $this->displayName,
            'avatar_url' => $this->avatarUrl,
            'email_verified_at' => $this->emailVerifiedAt,
            'verified' => $this->verified,
            'raw' => $this->raw,
        ];
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            userId: $payload['user_id'],
            displayName: $payload['display_name'] ?? null,
            avatarUrl: $payload['avatar_url'] ?? null,
            emailVerifiedAt: $payload['email_verified_at'] ?? null,
            verified: (bool) ($payload['verified'] ?? false),
            raw: $payload['raw'] ?? [],
        );
    }

    private static function firstValue(array $payload, array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = data_get($payload, $key);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private static function stringValue(array $payload, array $keys): ?string
    {
        $value = self::firstValue($payload, $keys);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function booleanValue(array $payload, array $keys): ?bool
    {
        $value = self::firstValue($payload, $keys);

        return is_bool($value) ? $value : null;
    }
}
