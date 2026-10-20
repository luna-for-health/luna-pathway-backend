<?php

namespace Tests\Feature\Mail;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BrevoTestCommandTest extends TestCase
{
    private const ENDPOINT = 'https://api.brevo.com/v3/smtp/email';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.brevo.api_key' => 'test-api-key']);
        Http::preventStrayRequests();
    }

    public function test_sends_a_test_email_to_the_given_address(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['messageId' => '<abc@brevo>'], 201)]);

        $this->artisan('brevo:test', ['email' => 'dev@example.com'])
            ->expectsOutput('Test email sent to dev@example.com (message id: <abc@brevo>).')
            ->assertSuccessful();

        Http::assertSent(fn (Request $request): bool => $request['to'] === [['email' => 'dev@example.com']]
            && $request['subject'] === 'Brevo test email');
    }

    public function test_exits_with_failure_when_brevo_rejects_the_request(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['message' => 'Key not found'], 401)]);

        $this->artisan('brevo:test', ['email' => 'dev@example.com'])
            ->expectsOutput('Brevo API returned HTTP 401: Key not found')
            ->assertFailed();
    }
}
