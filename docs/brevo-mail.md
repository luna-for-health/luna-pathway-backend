# Brevo Mail

Send transactional email through the [Brevo API v3](https://developers.brevo.com/reference/sendtransacemail) in one line, from anywhere in the code.

```php
use App\Services\Mail\BrevoMail;

BrevoMail::queue($user->email, 'emails.welcome', ['name' => $user->first_name]);
```

This is separate from Laravel's mailer. The existing Mailables (`MagicCodeMail`, `SecretaryPasswordResetMail`) still go through `MAIL_MAILER`. Only code that calls `BrevoMail` uses Brevo.

## Setup

Add the following to `.env`:

| Variable | Required | Default | Description |
| --- | --- | --- | --- |
| `BREVO_API_KEY` | yes | — | API key from Brevo → SMTP & API → API Keys |
| `BREVO_FROM_ADDRESS` | yes* | `MAIL_FROM_ADDRESS` | Sender address. It must be a verified sender in Brevo |
| `BREVO_FROM_NAME` | no | `MAIL_FROM_NAME` | Sender name |
| `BREVO_DEFAULT_SUBJECT` | no | `APP_NAME` | Subject used when none is given |
| `BREVO_TIMEOUT` | no | `10` | HTTP timeout in seconds |

\* It falls back to `MAIL_FROM_ADDRESS`.

These values are read from `config('services.brevo.*')` (see `config/services.php`). Do not call `env()` outside config files.

Check the setup by sending a real email:

```bash
php artisan brevo:test you@example.com
```

Queued mail needs a running worker. The queue uses the `database` driver:

```bash
php artisan queue:work
```

## Usage

### Send now

```php
$messageId = BrevoMail::send('user@example.com', 'emails.welcome', ['name' => 'Bilel']);
```

The view is rendered right away and the email is sent. The call returns Brevo's `messageId`.

### Queue

```php
BrevoMail::queue('user@example.com', 'emails.welcome', ['name' => 'Bilel']);
```

This dispatches `SendBrevoMailJob`. The Blade view is rendered **inside the job**, so the queued payload only holds the view name and the data.

Use `queue()` in HTTP requests so the user doesn't wait on the Brevo API.

### Subject

```php
BrevoMail::queue('user@example.com', 'emails.welcome', ['name' => 'Bilel'], 'Welcome');
```

The subject is resolved in this order:

1. The 4th argument, or `->subject()` in the fluent form
2. `$data['subject']`, which is then removed from the data passed to the view
3. `config('services.brevo.default_subject')`

### Brevo template

```php
BrevoMail::template('user@example.com', 12, ['name' => 'Bilel']);
```

The template is designed in the Brevo dashboard. The params are available there as `{{ params.name }}`. The template's own subject is used unless you set one with `->subject()`.

### Recipients

`to`, `cc` and `bcc` accept any of these forms:

```php
'a@example.com'
['a@example.com', 'b@example.com']
['a@example.com' => 'Alice', 'b@example.com' => 'Bob']
```

### Fluent form (advanced)

```php
BrevoMail::to([$doctor->email => $doctor->name])
    ->cc('secretariat@example.com')
    ->bcc('audit@example.com')
    ->replyTo('support@example.com', 'Support')
    ->attach(storage_path('app/report.pdf'), 'compte-rendu.pdf')
    ->tags(['consultation', 'report'])
    ->view('emails.report', $data, 'Votre compte-rendu')
    ->queue();          // or ->send(), or ->queue('emails') for a named queue
```

| Method | Description |
| --- | --- |
| `to($address)` / `cc($address)` / `bcc($address)` | Add recipients. You can call each one more than once |
| `replyTo($email, $name = null)` | Reply-to address |
| `subject($subject)` | Explicit subject |
| `view($view, $data = [], $subject = null)` | Blade view body |
| `html($html)` | Raw HTML body |
| `template($id, $params = [])` | Brevo template body |
| `attach($path, $name = null)` | Attach a local file. It is read when the email is sent |
| `attachData($contents, $name)` | Attach raw content. It is stored in the job payload |
| `tags($tags)` | Brevo tags (string or array), useful for filtering stats |
| `send()` | Send now and return the `messageId` |
| `queue($queue = null)` | Dispatch `SendBrevoMailJob` |

Only one body is used per email: `view`, `html` or `template`. The last one called wins.

For queued mail, prefer `attach()` with a file path over `attachData()`, so the job payload stays small. The file must still exist when the worker runs.

## Emails sent by the app

| Trigger | Recipient | Brevo template (env) | Params |
| --- | --- | --- | --- |
| Consultation request created (`POST /consultation-requests`, `User\Appointment\ConsultationRequestController::store`) | The patient | `BREVO_TEMPLATE_CONSULTATION_REQUEST_CREATED` | `PRENOM` (user `last_name`), `lastName`, `firstName`, `consultationRequestRef`, `createdAt` (`d/m/Y H:i`) |

Each one is queued and tagged with its trigger name, for example `consultation-request-created`. If the template env var is empty, or the patient has no email address, no email is sent and the request still succeeds.

Medical details (reason, history, symptoms) are deliberately **not** sent to Brevo as params.

## Error handling

Every failure throws `App\Services\Mail\BrevoMailException`:

| Case | `status` | `retryable` |
| --- | --- | --- |
| No API key, no recipient, no body, unreadable attachment | `null` | `false` |
| Brevo returns 4xx, for example an invalid email or a bad key | the HTTP code | `false` |
| Brevo returns 429 or 5xx | the HTTP code | `true` |
| Network or timeout error | `null` | `true` |

```php
use App\Services\Mail\BrevoMailException;

try {
    BrevoMail::send($email, 'emails.welcome', $data);
} catch (BrevoMailException $e) {
    report($e);
}
```

How the queued job handles failures:

- It retries **3 times**, waiting 10s, 60s and 300s, but only for retryable errors.
- It fails immediately on non-retryable errors, such as a 4xx error or bad configuration.
- On final failure, `failed()` logs `Brevo mail job failed` with the view or template id, the recipient count and the error.

**Logs never contain** the API key, the email body or the recipient addresses. API errors log only the HTTP status and Brevo's `code` and `message`.

## Testing

Fake the Brevo endpoint with Laravel's HTTP client:

```php
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

Http::preventStrayRequests();
Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response(['messageId' => 'id'], 201)]);

// ... code under test ...

Http::assertSent(fn (Request $request) => $request['to'] === [['email' => 'user@example.com']]);
```

For queued mail, fake the job:

```php
use App\Jobs\SendBrevoMailJob;
use Illuminate\Support\Facades\Queue;

Queue::fake([SendBrevoMailJob::class]);

// ... code under test ...

Queue::assertPushed(SendBrevoMailJob::class, fn ($job) => $job->message->view === 'emails.welcome');
```

See `tests/Feature/Mail/` for complete examples.

## Files

| File | Role |
| --- | --- |
| `app/Services/Mail/BrevoMailService.php` | Service: builds the payload and calls the Brevo API |
| `app/Services/Mail/BrevoMessage.php` | Fluent, serializable message builder |
| `app/Services/Mail/BrevoMail.php` | Facade |
| `app/Services/Mail/BrevoMailException.php` | Exception with `status` and `retryable` |
| `app/Jobs/SendBrevoMailJob.php` | Queued sending, with retries and failure logging |
| `app/Providers/AppServiceProvider.php` | Registers the service as a singleton |
| `routes/console.php` | `brevo:test {email}` command |
| `resources/views/emails/brevo-test.blade.php` | View used by `brevo:test` |
| `config/services.php` | `brevo` config block |
