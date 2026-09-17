<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:1024'],
            'remember' => ['sometimes', 'boolean'],
        ], [
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'username.max' => 'Tên đăng nhập không quá 100 ký tự.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.max' => 'Mật khẩu quá dài.',
        ]);

        $username = Str::lower(trim($credentials['username']));
        $key = 'admin-login:'.hash('sha256', $username.'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'username' => "Bạn đã thử đăng nhập quá nhiều lần. Vui lòng thử lại sau {$seconds} giây.",
            ]);
        }

        if (! Auth::guard('web')->attempt([
            'username' => $username,
            'password' => $credentials['password'],
            'is_admin' => true,
        ], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages([
                'username' => 'Tên đăng nhập hoặc mật khẩu không đúng.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('signin');
    }
}
