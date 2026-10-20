<?php

namespace Tests\Feature\Mail;

use App\Jobs\SendBrevoMailJob;
use App\Services\Mail\BrevoMailException;
use App\Services\Mail\BrevoMailService;
use App\Services\Mail\BrevoMessage;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use RuntimeException;
use Tests\TestCase;

class SendBrevoMailJobTest extends TestCase
{
    private const ENDPOINT = 'https://api.brevo.com/v3/smtp/email';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.brevo.api_key' => 'test-api-key']);
        View::addLocation(base_path('tests/Fixtures/views'));
        Http::preventStrayRequests();
    }

    private function job(): SendBrevoMailJob
    {
        $message = (new BrevoMessage)->to('patient@example.com')->view('brevo-fixture', ['name' => 'Bilel']);

        return (new SendBrevoMailJob($message))->withFakeQueueInteractions();
    }

    public function test_renders_the_view_and_sends_it_when_handled(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['messageId' => 'id'], 201)]);
        $job = $this->job();

        $job->handle(app(BrevoMailService::class));

        $job->assertNotFailed();
        Http::assertSent(fn (Request $request): bool => $request['htmlContent'] === 'Hello Bilel');
    }

    public function test_client_error_fails_the_job_without_retrying(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['message' => 'invalid email'], 400)]);
        $job = $this->job();

        $job->handle(app(BrevoMailService::class));

        $job->assertFailed();
    }

    public function test_server_error_is_rethrown_so_the_job_is_retried(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['message' => 'oops'], 503)]);
        $job = $this->job();

        $this->expectException(BrevoMailException::class);

        $job->handle(app(BrevoMailService::class));
    }

    public function test_failed_handler_logs_without_recipient_addresses(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Brevo mail job failed', [
                'view' => 'brevo-fixture',
                'template_id' => null,
                'recipients' => 1,
                'attempts' => 1,
                'exception' => 'boom',
            ]);

        $this->job()->failed(new RuntimeException('boom'));
    }
}
