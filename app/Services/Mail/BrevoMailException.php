<?php

namespace App\Services\Mail;

use RuntimeException;
use Throwable;

class BrevoMailException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly bool $retryable = false,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status ?? 0, $previous);
    }

    /**
     * The Brevo API answered with a non-2xx status.
     */
    public static function requestFailed(int $status, ?string $brevoMessage): self
    {
        return new self(
            'Brevo API returned HTTP '.$status.($brevoMessage ? ': '.$brevoMessage : ''),
            $status,
            $status === 429 || $status >= 500,
        );
    }

    /**
     * The Brevo API could not be reached.
     */
    public static function connectionFailed(Throwable $previous): self
    {
        return new self('Brevo API unreachable: '.$previous->getMessage(), retryable: true, previous: $previous);
    }

    /**
     * The message or configuration is incomplete, so no request was sent.
     */
    public static function invalid(string $reason): self
    {
        return new self('Brevo mail not sent: '.$reason);
    }
}
