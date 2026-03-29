<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(AdminLoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $admin = Admin::query()->where('email', $credentials['email'])->first();

        if (! $admin || $admin->status !== 'active' || ! Hash::check($credentials['password'], $admin->password)) {
            return back()
                ->withErrors(['email' => 'The provided admin credentials are invalid.'])
                ->onlyInput('email');
        }

        Auth::guard('admin')->login($admin, remember: false);
        $request->session()->regenerate();

        $admin->forceFill(['last_login_at' => now()])->save();

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'Signed in successfully.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('admin.login')
            ->with('status', 'Signed out successfully.');
    }
}
