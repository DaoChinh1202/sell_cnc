<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Mailtrap\Bridge\Transport\MailtrapSdkTransport;
use Mailtrap\Bridge\Transport\MailtrapSdkTransportFactory;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mailer\Transport\Dsn;
use Tests\TestCase;

class MailtrapIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config([
            'mail.default' => 'mailtrap-sdk',
            'mail.from.address' => 'hello@demomailtrap.co',
            'mail.from.name' => 'Kho mẫu 3D',
            'services.mailtrap-sdk.apiKey' => 'fake-test-token',
            'services.mailtrap-sdk.host' => 'send.api.mailtrap.io',
            'services.mailtrap_test_to' => 'owner@example.com',
            'app.url' => 'https://khomau3d.example',
        ]);
    }

    private function interceptMailtrap(callable $callback): void
    {
        // Exercise the SDK serialization/HTTP path without sending real emails.
        $mailer = Mail::mailer('mailtrap-sdk');
        $this->assertInstanceOf(MailtrapSdkTransport::class, $mailer->getSymfonyTransport());
        $transport = (new MailtrapSdkTransportFactory(client: new MockHttpClient($callback)))
            ->create(new Dsn('mailtrap+sdk', 'send.api.mailtrap.io', 'fake-test-token'));
        $mailer->setSymfonyTransport($transport);
    }

    public function test_send_mail_command_uses_configured_sender_recipient_and_category(): void
    {
        $requests = 0;
        $this->interceptMailtrap(function ($method, $url, $options) use (&$requests) {
            $requests++;
            $this->assertSame('POST', $method);
            $this->assertSame('https://send.api.mailtrap.io/api/send', $url);
            $this->assertContains('Authorization: Bearer fake-test-token', $options['headers']);
            $data = json_decode($options['body'], true);
            $this->assertSame('owner@example.com', $data['to'][0]['email']);
            $this->assertSame('hello@demomailtrap.co', $data['from']['email']);
            $this->assertSame('Kho mẫu 3D', $data['from']['name']);
            $this->assertSame('Integration Test', $data['category']);
            $this->assertStringContainsString('kiểm tra', $data['text']);

            return new MockResponse('{"success":true,"message_ids":["test-message"]}', ['http_code' => 200, 'response_headers' => ['content-type: application/json']]);
        });
        $this->artisan('send-mail')->expectsOutputToContain('Mailtrap đã tiếp nhận')->assertSuccessful();
        $this->assertSame(1, $requests);
    }

    public function test_send_mail_accepts_explicit_recipient(): void
    {
        $this->interceptMailtrap(function ($method, $url, $options) {
            $this->assertSame('another@example.com', json_decode($options['body'], true)['to'][0]['email']);

            return new MockResponse('{"success":true,"message_ids":["test-message"]}', ['response_headers' => ['content-type: application/json']]);
        });
        $this->artisan('send-mail', ['to' => 'another@example.com'])->assertSuccessful();
    }

    public function test_command_rejects_missing_token_or_invalid_addresses_without_sending(): void
    {
        Mail::shouldReceive('mailer')->never();
        config(['services.mailtrap-sdk.apiKey' => '']);
        $this->artisan('send-mail')->expectsOutputToContain('MAILTRAP_API_KEY')->assertFailed();
        config(['services.mailtrap-sdk.apiKey' => 'fake-test-token', 'services.mailtrap_test_to' => '']);
        $this->artisan('send-mail')->assertFailed();
        $this->artisan('send-mail', ['to' => 'not-an-email'])->assertFailed();
        config(['mail.from.address' => 'invalid']);
        $this->artisan('send-mail', ['to' => 'owner@example.com'])->assertFailed();
    }

    public function test_api_rejection_reports_original_http_status_and_redacts_credentials(): void
    {
        $this->interceptMailtrap(fn () => new MockResponse('{"errors":["fake-test-token private-response"]}', ['http_code' => 403, 'response_headers' => ['content-type: application/json']]));
        $this->artisan('send-mail')->expectsOutputToContain('Không gửi được email. Mailtrap trả HTTP 403.')
            ->expectsOutputToContain('[REDACTED] private-response')
            ->doesntExpectOutputToContain('fake-test-token')->assertFailed();
    }

    public function test_invalid_token_error_has_actionable_guidance(): void
    {
        $this->interceptMailtrap(fn () => new MockResponse('{"error":"Incorrect API token"}', ['http_code' => 401, 'response_headers' => ['content-type: application/json']]));
        $this->artisan('send-mail')->expectsOutputToContain('HTTP 401')
            ->expectsOutputToContain('Incorrect API token')
            ->expectsOutputToContain('Kiểm tra MAILTRAP_API_KEY')->assertFailed();
    }

    public function test_connection_failure_does_not_print_exception_credentials(): void
    {
        $this->interceptMailtrap(fn () => throw new TransportException('Connection failed: fake-test-token'));
        $this->artisan('send-mail')->expectsOutputToContain('Không gửi được email')
            ->expectsOutputToContain('HTTPS/DNS/TLS')->doesntExpectOutputToContain('fake-test-token')->assertFailed();
    }

    public function test_forgot_password_uses_mailtrap_and_sends_the_reset_link_to_the_admin(): void
    {
        Event::fake([MessageSent::class]);
        $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
        $requests = 0;
        $this->interceptMailtrap(function ($method, $url, $options) use (&$requests) {
            $requests++;
            $data = json_decode($options['body'], true);
            $this->assertSame('admin@example.com', $data['to'][0]['email']);
            $this->assertStringContainsString('Đặt lại mật khẩu', $data['subject']);
            $this->assertStringContainsString('https://khomau3d.example/admin/reset-password/', $data['html']);
            $this->assertStringContainsString('admin%40example.com', $data['html']);

            return new MockResponse('{"success":true,"message_ids":["reset-message"]}', ['response_headers' => ['content-type: application/json']]);
        });
        $this->post(route('password.email'), ['username' => $admin->username])->assertSessionHas('status');
        Event::assertDispatched(MessageSent::class);
        $this->assertSame(1, $requests);
    }
}
