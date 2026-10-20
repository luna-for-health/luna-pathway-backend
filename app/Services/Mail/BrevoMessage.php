<?php

namespace App\Services\Mail;

use App\Jobs\SendBrevoMailJob;

/**
 * Fluent, serializable description of a Brevo email.
 *
 * Holds only scalars and arrays so it can be queued as-is: Blade views are
 * rendered and attachment files are read when the message is delivered.
 */
class BrevoMessage
{
    /** @var list<array{email: string, name?: string}> */
    public array $to = [];

    /** @var list<array{email: string, name?: string}> */
    public array $cc = [];

    /** @var list<array{email: string, name?: string}> */
    public array $bcc = [];

    /** @var array{email: string, name?: string}|null */
    public ?array $replyTo = null;

    public ?string $subject = null;

    /** Subject taken from the view data, used when no explicit subject is set. */
    public ?string $dataSubject = null;

    public ?string $view = null;

    /** @var array<string, mixed> */
    public array $viewData = [];

    public ?string $html = null;

    public ?int $templateId = null;

    /** @var array<string, mixed> */
    public array $params = [];

    /** @var list<array{path?: string, content?: string, name: string}> */
    public array $attachments = [];

    /** @var list<string> */
    public array $tags = [];

    /**
     * @param  string|array<int|string, string>  $address
     */
    public function to(string|array $address): static
    {
        $this->to = [...$this->to, ...self::normalizeRecipients($address)];

        return $this;
    }

    /**
     * @param  string|array<int|string, string>  $address
     */
    public function cc(string|array $address): static
    {
        $this->cc = [...$this->cc, ...self::normalizeRecipients($address)];

        return $this;
    }

    /**
     * @param  string|array<int|string, string>  $address
     */
    public function bcc(string|array $address): static
    {
        $this->bcc = [...$this->bcc, ...self::normalizeRecipients($address)];

        return $this;
    }

    public function replyTo(string $email, ?string $name = null): static
    {
        $this->replyTo = $name === null ? ['email' => $email] : ['email' => $email, 'name' => $name];

        return $this;
    }

    public function subject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Use a Blade view as the body. A `subject` key in $data is used as the subject
     * (unless one is set explicitly) and is not passed to the view.
     *
     * @param  array<string, mixed>  $data
     */
    public function view(string $view, array $data = [], ?string $subject = null): static
    {
        if (array_key_exists('subject', $data)) {
            $this->dataSubject = $data['subject'] === null ? null : (string) $data['subject'];
            unset($data['subject']);
        }

        $this->view = $view;
        $this->viewData = $data;
        $this->html = null;
        $this->templateId = null;

        if ($subject !== null) {
            $this->subject = $subject;
        }

        return $this;
    }

    public function html(string $html): static
    {
        $this->html = $html;
        $this->view = null;
        $this->templateId = null;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function template(int $templateId, array $params = []): static
    {
        $this->templateId = $templateId;
        $this->params = $params;
        $this->view = null;
        $this->html = null;

        return $this;
    }

    /**
     * Attach a local file. It is read when the message is delivered.
     */
    public function attach(string $path, ?string $name = null): static
    {
        $this->attachments[] = ['path' => $path, 'name' => $name ?? basename($path)];

        return $this;
    }

    /**
     * Attach raw content. Prefer attach() for queued mail to keep the job payload small.
     */
    public function attachData(string $contents, string $name): static
    {
        $this->attachments[] = ['content' => base64_encode($contents), 'name' => $name];

        return $this;
    }

    /**
     * @param  string|list<string>  $tags
     */
    public function tags(string|array $tags): static
    {
        $this->tags = array_values(array_unique([...$this->tags, ...(array) $tags]));

        return $this;
    }

    /**
     * Send immediately and return the Brevo message id.
     *
     * @throws BrevoMailException
     */
    public function send(): ?string
    {
        return app(BrevoMailService::class)->deliver($this);
    }

    /**
     * Send through the queue.
     */
    public function queue(?string $queue = null): void
    {
        $job = new SendBrevoMailJob($this);

        if ($queue !== null) {
            $job->onQueue($queue);
        }

        dispatch($job);
    }

    /**
     * Accepts 'a@x.com', ['a@x.com', 'b@x.com'] or ['a@x.com' => 'Name'].
     *
     * @param  string|array<int|string, string>  $address
     * @return list<array{email: string, name?: string}>
     */
    private static function normalizeRecipients(string|array $address): array
    {
        $recipients = [];

        foreach ((array) $address as $key => $value) {
            $recipients[] = is_string($key)
                ? ['email' => $key, 'name' => $value]
                : ['email' => $value];
        }

        return $recipients;
    }
}
