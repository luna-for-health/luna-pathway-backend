<?php

namespace App\Services\Mail;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends transactional email through the Brevo API v3.
 *
 * @see https://developers.brevo.com/reference/sendtransacemail
 */
class BrevoMailService
{
    public const ENDPOINT = 'https://api.brevo.com/v3/smtp/email';

    /**
     * Start a fluent message (cc, bcc, replyTo, attachments, tags...).
     *
     * @param  string|array<int|string, string>  $address
     */
    public function to(string|array $address): BrevoMessage
    {
        return (new BrevoMessage)->to($address);
    }

    /**
     * Render a Blade view and send it now. Returns the Brevo message id.
     *
     * @param  string|array<int|string, string>  $to
     * @param  array<string, mixed>  $data
     *
     * @throws BrevoMailException
     */
    public function send(string|array $to, string $view, array $data = [], ?string $subject = null): ?string
    {
        return $this->to($to)->view($view, $data, $subject)->send();
    }

    /**
     * Queue a Blade view email. The view is rendered by the job, not here.
     *
     * @param  string|array<int|string, string>  $to
     * @param  array<string, mixed>  $data
     */
    public function queue(string|array $to, string $view, array $data = [], ?string $subject = null): void
    {
        $this->to($to)->view($view, $data, $subject)->queue();
    }

    /**
     * Send a Brevo-hosted template now. Returns the Brevo message id.
     *
     * @param  string|array<int|string, string>  $to
     * @param  array<string, mixed>  $params
     *
     * @throws BrevoMailException
     */
    public function template(string|array $to, int $templateId, array $params = []): ?string
    {
        return $this->to($to)->template($templateId, $params)->send();
    }

    /**
     * Build the API payload for the message and post it to Brevo.
     *
     * @throws BrevoMailException
     */
    public function deliver(BrevoMessage $message): ?string
    {
        $apiKey = config('services.brevo.api_key');

        if (blank($apiKey)) {
            throw BrevoMailException::invalid('services.brevo.api_key is not configured.');
        }

        $payload = $this->buildPayload($message);

        try {
            $response = Http::withHeaders(['api-key' => $apiKey])
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('services.brevo.timeout', 10))
                ->post(self::ENDPOINT, $payload);
        } catch (ConnectionException $exception) {
            Log::error('Brevo mail request failed', ['exception' => $exception->getMessage()]);

            throw BrevoMailException::connectionFailed($exception);
        }

        if (! $response->successful()) {
            $brevoMessage = $response->json('message');
            $brevoMessage = is_string($brevoMessage) ? $brevoMessage : null;

            Log::error('Brevo mail request returned an error status', [
                'status' => $response->status(),
                'code' => $response->json('code'),
                'message' => $brevoMessage,
            ]);

            throw BrevoMailException::requestFailed($response->status(), $brevoMessage);
        }

        return $response->json('messageId');
    }

    /**
     * @return array<string, mixed>
     *
     * @throws BrevoMailException
     */
    private function buildPayload(BrevoMessage $message): array
    {
        if ($message->to === []) {
            throw BrevoMailException::invalid('no recipient.');
        }

        $payload = [
            'sender' => array_filter([
                'email' => config('services.brevo.from_address'),
                'name' => config('services.brevo.from_name'),
            ]),
            'to' => $message->to,
        ];

        if ($message->templateId !== null) {
            $payload['templateId'] = $message->templateId;

            if ($message->params !== []) {
                $payload['params'] = $message->params;
            }

            if ($message->subject !== null) {
                $payload['subject'] = $message->subject;
            }
        } elseif ($message->view !== null || $message->html !== null) {
            $payload['htmlContent'] = $message->view !== null
                ? view($message->view, $message->viewData)->render()
                : $message->html;
            $payload['subject'] = $message->subject
                ?? $message->dataSubject
                ?? config('services.brevo.default_subject');
        } else {
            throw BrevoMailException::invalid('no view, html or template given.');
        }

        if ($message->cc !== []) {
            $payload['cc'] = $message->cc;
        }

        if ($message->bcc !== []) {
            $payload['bcc'] = $message->bcc;
        }

        if ($message->replyTo !== null) {
            $payload['replyTo'] = $message->replyTo;
        }

        if ($message->tags !== []) {
            $payload['tags'] = $message->tags;
        }

        if ($message->attachments !== []) {
            $payload['attachment'] = array_map($this->buildAttachment(...), $message->attachments);
        }

        return $payload;
    }

    /**
     * @param  array{path?: string, content?: string, name: string}  $attachment
     * @return array{content: string, name: string}
     *
     * @throws BrevoMailException
     */
    private function buildAttachment(array $attachment): array
    {
        if (isset($attachment['content'])) {
            return ['content' => $attachment['content'], 'name' => $attachment['name']];
        }

        $path = $attachment['path'] ?? '';

        if (! is_file($path) || ! is_readable($path)) {
            throw BrevoMailException::invalid('attachment "'.$attachment['name'].'" is not readable.');
        }

        return ['content' => base64_encode(file_get_contents($path)), 'name' => $attachment['name']];
    }
}
