<?php

use App\Services\Mail\BrevoMail;
use App\Services\Mail\BrevoMailException;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('brevo:test {email}', function (string $email) {
    try {
        $messageId = BrevoMail::send($email, 'emails.brevo-test', [], 'Brevo test email');
    } catch (BrevoMailException $exception) {
        $this->error($exception->getMessage());

        return 1;
    }

    $this->info('Test email sent to '.$email.' (message id: '.($messageId ?? 'n/a').').');

    return 0;
})->purpose('Send a test email through the Brevo API');
