<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Models\Admin;
use App\Models\AdminApiToken;
use App\Services\AdminAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuthController extends Controller
{
    public function __construct(private readonly AdminAuthService $authService)
    {
    }

    public function login(AdminLoginRequest $request): JsonResponse
    {
        $payload = $this->authService->login(
            $request->validated('email'),
            $request->validated('password'),
            $request->validated('token_name', 'dashboard'),
        );

        return response()->json($payload);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        return response()->json([
            'admin' => $this->authService->adminPayload($admin),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var AdminApiToken $token */
        $token = $request->attributes->get('current_admin_token');
        $this->authService->logout($token);

        return response()->json([
            'message' => 'Admin session revoked.',
        ]);
    }
}
