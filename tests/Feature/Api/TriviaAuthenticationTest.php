<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GeneratesJwtTokens;
use Tests\TestCase;

class TriviaAuthenticationTest extends TestCase
{
    use GeneratesJwtTokens;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureJwtTestEnvironment();
    }

    public function test_valid_authbox_jwt_is_accepted_for_protected_route(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/v1/trivia/today');

        $response->assertOk()
            ->assertJson([
                'date' => now()->toDateString(),
                'available' => false,
                'state' => 'no_quiz',
                'quiz' => null,
                'current_attempt' => null,
            ]);
    }

    public function test_expired_jwt_is_rejected(): void
    {
        $response = $this->withHeaders($this->authHeaders([
            'exp' => time() - 3600,
        ]))->getJson('/api/v1/trivia/today');

        $response->assertUnauthorized()
            ->assertJson([
                'code' => 'AUTH_TOKEN_EXPIRED',
            ]);
    }

    public function test_missing_service_key_is_rejected(): void
    {
        $headers = $this->authHeaders();
        unset($headers['X-TNBO-Service-Key']);

        $response = $this->withHeaders($headers)
            ->getJson('/api/v1/trivia/today');

        $response->assertForbidden()
            ->assertJson([
                'code' => 'SERVICE_UNAUTHORIZED',
            ]);
    }
}
