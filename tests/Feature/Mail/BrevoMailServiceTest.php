<?php

namespace Tests\Feature\Mail;

use App\Jobs\SendBrevoMailJob;
use App\Services\Mail\BrevoMail;
use App\Services\Mail\BrevoMailException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class BrevoMailServiceTest extends TestCase
{
    private const ENDPOINT = 'https://api.brevo.com/v3/smtp/email';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.brevo.api_key' => 'test-api-key',
            'services.brevo.from_address' => 'noreply@luna.test',
            'services.brevo.from_name' => 'Luna',
            'services.brevo.default_subject' => 'Default subject',
        ]);

        View::addLocation(base_path('tests/Fixtures/views'));
        Http::preventStrayRequests();
    }

    public function test_send_posts_the_rendered_view_to_brevo_and_returns_the_message_id(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['messageId' => '<abc@brevo>'], 201)]);

        $messageId = BrevoMail::send('patient@example.com', 'brevo-fixture', ['name' => 'Bilel']);

        $this->assertSame('<abc@brevo>', $messageId);
        Http::assertSent(fn (Request $request): bool => $request->url() === self::ENDPOINT
            && $request->method() === 'POST'
            && $request->header('api-key') === ['test-api-key']
            && $request->data() === [
                'sender' => ['email' => 'noreply@luna.test', 'name' => 'Luna'],
                'to' => [['email' => 'patient@example.com']],
                'htmlContent' => 'Hello Bilel',
                'subject' => 'Default subject',
            ]);
    }

    public function test_explicit_subject_takes_precedence_over_the_data_subject(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['messageId' => 'id'], 201)]);

        BrevoMail::send('patient@example.com', 'brevo-fixture', ['name' => 'Bilel', 'subject' => 'From data'], 'Explicit');

        Http::assertSent(fn (Request $request): bool => $request['subject'] === 'Explicit'
            && $request['htmlContent'] === 'Hello Bilel');
    }

    public function test_data_subject_is_used_and_not_passed_to_the_view(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['messageId' => 'id'], 201)]);

        BrevoMail::send('patient@example.com', 'brevo-fixture', ['name' => 'Bilel', 'subject' => 'From data']);

        Http::assertSent(fn (Request $request): bool => $request['subject'] === 'From data'
            && $request['htmlContent'] === 'Hello Bilel');
    }

    public function test_template_sends_the_template_id_and_params_without_html_or_default_subject(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['messageId' => 'id'], 201)]);

        BrevoMail::template(['patient@example.com' => 'Bilel'], 42, ['name' => 'Bilel']);

        Http::assertSent(fn (Request $request): bool => $request->data() === [
            'sender' => ['email' => 'noreply@luna.test', 'name' => 'Luna'],
            'to' => [['email' => 'patient@example.com', 'name' => 'Bilel']],
            'templateId' => 42,
            'params' => ['name' => 'Bilel'],
        ]);
    }

    public function test_fluent_message_sends_cc_bcc_reply_to_tags_and_attachments(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['messageId' => 'id'], 201)]);
        $path = base_path('tests/Fixtures/views/brevo-fixture.blade.php');

        BrevoMail::to(['a@example.com', 'b@example.com'])
            ->cc('cc@example.com')
            ->bcc(['bcc@example.com' => 'Audit'])
            ->replyTo('support@luna.test', 'Support')
            ->tags(['onboarding', 'welcome'])
            ->attach($path, 'file.txt')
            ->attachData('raw', 'raw.txt')
            ->html('<p>Hi</p>')
            ->subject('Hello')
            ->send();

        Http::assertSent(fn (Request $request): bool => $request['to'] === [['email' => 'a@example.com'], ['email' => 'b@example.com']]
            && $request['cc'] === [['email' => 'cc@example.com']]
            && $request['bcc'] === [['email' => 'bcc@example.com', 'name' => 'Audit']]
            && $request['replyTo'] === ['email' => 'support@luna.test', 'name' => 'Support']
            && $request['tags'] === ['onboarding', 'welcome']
            && $request['htmlContent'] === '<p>Hi</p>'
            && $request['subject'] === 'Hello'
            && $request['attachment'] === [
                ['content' => base64_encode(file_get_contents($path)), 'name' => 'file.txt'],
                ['content' => base64_encode('raw'), 'name' => 'raw.txt'],
            ]);
    }

    public function test_error_response_throws_and_logs_the_status_without_secrets_or_body(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['code' => 'unauthorized', 'message' => 'Key not found'], 401)]);
        Log::shouldReceive('error')
            ->once()
            ->with('Brevo mail request returned an error status', [
                'status' => 401,
                'code' => 'unauthorized',
                'message' => 'Key not found',
            ]);

        try {
            BrevoMail::send('patient@example.com', 'brevo-fixture', ['name' => 'Confidential body']);
            $this->fail('BrevoMailException was not thrown.');
        } catch (BrevoMailException $exception) {
            $this->assertSame(401, $exception->status);
            $this->assertFalse($exception->retryable);
            $this->assertSame('Brevo API returned HTTP 401: Key not found', $exception->getMessage());
        }
    }

    public function test_missing_api_key_throws_without_calling_brevo(): void
    {
        config(['services.brevo.api_key' => null]);
        Http::fake();

        $this->expectException(BrevoMailException::class);

        try {
            BrevoMail::send('patient@example.com', 'brevo-fixture', ['name' => 'Bilel']);
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_queue_dispatches_the_job_without_rendering_or_calling_brevo(): void
    {
        Queue::fake([SendBrevoMailJob::class]);
        Http::fake();

        BrevoMail::queue('patient@example.com', 'brevo-fixture', ['name' => 'Bilel', 'subject' => 'From data'], 'Welcome');

        Queue::assertPushed(SendBrevoMailJob::class, fn (SendBrevoMailJob $job): bool => $job->message->view === 'brevo-fixture'
            && $job->message->viewData === ['name' => 'Bilel']
            && $job->message->subject === 'Welcome'
            && $job->message->to === [['email' => 'patient@example.com']]);
        Http::assertNothingSent();
    }
}
