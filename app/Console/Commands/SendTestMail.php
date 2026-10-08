<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Mailtrap\EmailHeader\CategoryHeader;
use Mailtrap\Exception\HttpException;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class SendTestMail extends Command
{
    protected $signature = 'send-mail {to? : Email nhận thử; mặc định lấy từ MAILTRAP_TEST_TO}';

    protected $description = 'Gửi email thử qua Mailtrap API bằng cấu hình trong .env';

    public function handle(): int
    {
        if (! filled(config('services.mailtrap-sdk.apiKey'))) {
            $this->error('Chưa cấu hình MAILTRAP_API_KEY. Điền token trong .env và cập nhật cache cấu hình.');

            return self::FAILURE;
        }

        $recipient = $this->argument('to') ?? config('services.mailtrap_test_to');
        $validator = Validator::make([
            'to' => $recipient,
            'from' => config('mail.from.address'),
        ], ['to' => ['required', 'email:rfc'], 'from' => ['required', 'email:rfc']]);
        if ($validator->fails()) {
            $this->error('Cần email nhận hợp lệ (đối số to hoặc MAILTRAP_TEST_TO) và MAIL_FROM_ADDRESS hợp lệ.');

            return self::FAILURE;
        }

        try {
            $sent = Mail::mailer('mailtrap-sdk')->raw('Email kiểm tra tích hợp Mailtrap từ Kho mẫu 3D.', function (Message $message) use ($recipient): void {
                $message->to($recipient)->subject('Kiểm tra gửi email — Kho mẫu 3D');
                $message->getSymfonyMessage()->getHeaders()->add(new CategoryHeader('Integration Test'));
            });
            if ($sent === null) {
                $this->error('Email chưa được gửi (thao tác bị hủy).');

                return self::FAILURE;
            }
        } catch (Throwable $exception) {
            $this->reportFailure($exception);

            return self::FAILURE;
        }

        $this->info('Mailtrap đã tiếp nhận email gửi đến '.$recipient.'. Kiểm tra hộp thư hoặc Email Logs trong Mailtrap.');

        return self::SUCCESS;
    }

    private function reportFailure(Throwable $exception): void
    {
        // Symfony wraps the SDK exception with code 0; find the original HTTP status.
        $cause = $exception;
        while (! $cause instanceof HttpException && $cause->getPrevious() !== null) {
            $cause = $cause->getPrevious();
        }

        $status = $cause instanceof HttpException ? (int) $cause->getCode() : null;
        Log::warning('Mailtrap test email failed.', ['exception' => $cause::class, 'http_status' => $status]);
        $this->error('Không gửi được email.'.($status ? ' Mailtrap trả HTTP '.$status.'.' : ''));

        if ($cause instanceof HttpException) {
            // Only show the SDK's parsed API error, never request headers or a trace.
            $detail = $cause->getMessage();
            $token = (string) config('services.mailtrap-sdk.apiKey');
            if ($token !== '') {
                $detail = str_replace(array_unique([$token, rawurlencode($token), base64_encode($token)]), '[REDACTED]', $detail);
            }
            $detail = preg_replace('/Bearer\s+\S+/i', 'Bearer [REDACTED]', $detail);
            $detail = preg_replace('/[\x00-\x1F\x7F]/', ' ', strip_tags($detail));
            $this->output->writeln('Chi tiết: '.mb_substr($detail, 0, 1000), OutputInterface::OUTPUT_RAW);
        }

        $this->warn(match ($status) {
            401 => 'Kiểm tra MAILTRAP_API_KEY còn hiệu lực và đã cập nhật cache cấu hình.',
            403 => 'Kiểm tra quyền gửi của token, domain đã xác minh và người nhận được phép nếu dùng domain demo.',
            400, 422 => 'Kiểm tra MAIL_FROM_ADDRESS, email người nhận và nội dung lỗi phía trên.',
            429 => 'Mailtrap đang giới hạn yêu cầu hoặc hạn mức gửi. Kiểm tra tài khoản trước khi thử lại.',
            500, 502, 503, 504 => 'Mailtrap đang gặp lỗi máy chủ. Thử lại sau.',
            default => 'Kiểm tra MAILTRAP_HOST, cấu hình SDK và kết nối HTTPS/DNS/TLS từ container tới Mailtrap.',
        });
    }
}
