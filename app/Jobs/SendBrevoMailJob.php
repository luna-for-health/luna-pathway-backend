<?php

namespace App\Jobs;

use App\Services\Mail\BrevoMailException;
use App\Services\Mail\BrevoMailService;
use App\Services\Mail\BrevoMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendBrevoMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(public BrevoMessage $message) {}

    public function handle(BrevoMailService $brevo): void
    {
        try {
            $brevo->deliver($this->message);
        } catch (BrevoMailException $exception) {
            if (! $exception->retryable) {
                $this->fail($exception);

                return;
            }

            throw $exception;
        }
    }

    /**
     * Recipients and body are deliberately left out of the log.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error('Brevo mail job failed', [
            'view' => $this->message->view,
            'template_id' => $this->message->templateId,
            'recipients' => count($this->message->to),
            'attempts' => $this->attempts(),
            'exception' => $exception?->getMessage(),
        ]);
    }
}
