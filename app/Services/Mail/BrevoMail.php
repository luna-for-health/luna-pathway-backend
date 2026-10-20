<?php

namespace App\Services\Mail;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string|null send(string|array $to, string $view, array $data = [], ?string $subject = null)
 * @method static void queue(string|array $to, string $view, array $data = [], ?string $subject = null)
 * @method static string|null template(string|array $to, int $templateId, array $params = [])
 * @method static BrevoMessage to(string|array $address)
 * @method static string|null deliver(BrevoMessage $message)
 *
 * @see BrevoMailService
 */
class BrevoMail extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BrevoMailService::class;
    }
}
