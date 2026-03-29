<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_and_fetch_current_profile(): void
    {
        Admin::create([
            'name' => 'Trivia Admin',
            'email' => 'admin@example.com',
            'password' => 'secret-pass',
            'role' => 'interactive_admin',
            'status' => 'active',
        ]);

        $login = $this->postJson('/api/admin/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'secret-pass',
        ]);

        $login->assertOk()
            ->assertJsonPath('admin.email', 'admin@example.com');

        $token = $login->json('token');

        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ])->getJson('/api/admin/auth/me')
            ->assertOk()
            ->assertJsonPath('admin.name', 'Trivia Admin');
    }

    public function test_admin_routes_require_a_valid_admin_token(): void
    {
        $this->getJson('/api/admin/trivia/quizzes')
            ->assertUnauthorized()
            ->assertJson([
                'code' => 'ADMIN_AUTH_REQUIRED',
            ]);
    }
}
