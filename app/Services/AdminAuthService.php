<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Admin;
use App\Models\AdminApiToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminAuthService
{
    public function login(string $email, string $password, string $tokenName = 'dashboard'): array
    {
        $admin = Admin::query()->where('email', $email)->first();

        if (! $admin || $admin->status !== 'active' || ! Hash::check($password, $admin->password)) {
            throw ApiException::unauthorized('The provided admin credentials are invalid.', 'ADMIN_INVALID_CREDENTIALS');
        }

        $plainTextToken = Str::random(80);
        $expiresAt = now()->addMinutes((int) config('admin.token_ttl_minutes', 480));

        $token = DB::transaction(function () use ($admin, $tokenName, $plainTextToken, $expiresAt): AdminApiToken {
            $admin->forceFill(['last_login_at' => now()])->save();

            return AdminApiToken::create([
                'admin_id' => $admin->id,
                'token_hash' => hash('sha256', $plainTextToken),
                'name' => $tokenName,
                'expires_at' => $expiresAt,
            ]);
        });

        return [
            'token' => $plainTextToken,
            'expires_at' => $token->expires_at?->toIso8601String(),
            'admin' => $this->adminPayload($admin),
        ];
    }

    public function logout(AdminApiToken $token): void
    {
        $token->forceFill(['revoked_at' => now()])->save();
    }

    public function adminPayload(Admin $admin): array
    {
        return [
            'id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => $admin->role,
            'status' => $admin->status,
            'last_login_at' => $admin->last_login_at?->toIso8601String(),
        ];
    }
}
