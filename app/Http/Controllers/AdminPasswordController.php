<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\AdminResetPassword;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Throwable;

class AdminPasswordController extends Controller
{
    public function sendLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['username' => ['required', 'string', 'max:100']], [
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'username.max' => 'Tên đăng nhập không quá 100 ký tự.',
        ]);

        try {
            Password::broker('users')->sendResetLink([
                'username' => Str::lower(trim($data['username'])),
                'is_admin' => true,
            ], function (User $user, string $token): void {
                if (! Str::endsWith(Str::lower($user->email), '.invalid')) {
                    $user->notify(new AdminResetPassword($token));
                }
            });
        } catch (Throwable $exception) {
            report($exception);
        }

        // Keep the same response for unknown users, non-admins and delivery failures.
        return back()->with('status', 'Nếu tài khoản quản trị có email khôi phục hợp lệ, bạn sẽ nhận được liên kết đặt lại mật khẩu. Vui lòng kiểm tra cả thư mục spam.');
    }

    public function resetForm(Request $request, string $token): Response
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);

        return response()->view('auth.reset-password', ['token' => $token, 'email' => $data['email']])
            ->header('Cache-Control', 'no-store, private')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', 'string', 'max:128', PasswordRule::min(8)->mixedCase()->numbers()->symbols()],
        ], [
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.confirmed' => 'Mật khẩu nhập lại chưa khớp.',
            'password.min' => 'Mật khẩu cần ít nhất 8 ký tự.',
            'password.mixed' => 'Mật khẩu cần có chữ hoa và chữ thường.',
            'password.numbers' => 'Mật khẩu cần có chữ số.',
            'password.symbols' => 'Mật khẩu cần có ký tự đặc biệt.',
        ]);

        $status = Password::broker('users')->reset($data + ['is_admin' => true], function (User $user, string $password): void {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu liên kết mới.']);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('signin')->with('status', 'Đã đặt lại mật khẩu. Hãy đăng nhập bằng mật khẩu mới.');
    }
}
