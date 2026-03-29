<?php

namespace App\Auth;

use Illuminate\Auth\GenericUser;

class JwtUser extends GenericUser
{
    public function __construct(
        string $userId,
        protected array $claims,
        protected string $token,
    ) {
        parent::__construct([
            'id' => $userId,
            'user_id' => $userId,
        ]);
    }

    public function userId(): string
    {
        return $this->getAuthIdentifier();
    }

    public function claims(): array
    {
        return $this->claims;
    }

    public function token(): string
    {
        return $this->token;
    }
}
