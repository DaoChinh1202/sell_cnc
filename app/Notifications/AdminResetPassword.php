<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class AdminResetPassword extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        // Use the configured public URL, never the requesting Host header.
        $url = rtrim(config('app.url'), '/').route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false);

        return (new MailMessage)
            ->subject('Đặt lại mật khẩu quản trị — khomau3d')
            ->greeting('Xin chào '.$notifiable->name.',')
            ->line('Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản quản trị của bạn.')
            ->action('Đặt lại mật khẩu', $url)
            ->line('Liên kết có hiệu lực trong '.config('auth.passwords.users.expire').' phút và chỉ sử dụng được một lần.')
            ->line('Nếu bạn không yêu cầu thay đổi mật khẩu, hãy bỏ qua email này.')
            ->salutation('khomau3d');
    }
}
