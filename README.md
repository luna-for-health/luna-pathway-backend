# Luna for Health Tunisia — Backend

Open-source platform that gets a woman or an adolescent girl with a women's health problem —
endometriosis first — to the right practitioner, on the right pathway, as fast as possible.

Built by [Luna for Health](https://lunaforhealth.com) with the support of the
[UNICEF Venture Fund](https://www.unicef.org/innovation/venturefund), FemTech cohort 2026–2027. The
method comes from Centre Luna Marseille (France); the first deployment under the Fund is Centre Luna
Tunisia.

This repository is the **backend API** — the clinical routing engine, centre-configuration tool, Luna
secretariat and Luna Doctors. The patient-facing intake application is a separate repository
(`luna-patient`).

## The problem

Endometriosis takes years to diagnose. Period pain is treated as normal, and nothing connects a woman
who suspects something is wrong to a clinician who can help her. In Tunisia, 68% of schoolgirls report
painful periods and most self-medicate rather than consult; there is no established care pathway and no
prevalence or diagnostic-delay study.

The decision that matters for her — *which professional should she see?* — is made at a medical centre's
secretariat, by a coordinator with years of experience and, until now, no tool. This platform captures
that coordinator's knowledge and runs it, so that every woman is routed on experience and the centre
keeps the final decision.

**It does not diagnose.** It prepares her consultation and gets her to the right person.

## Principles

- **Built for her, deployed at the centre.** The centre is the customer; the intake and the app are free
  for the woman or girl.
- **No prior medical history required.** A girl who has never seen a doctor is served as well as a woman
  with a folder of scans.
- **Routing on experience, not on a generic algorithm.** Rules come from the centre's own coordinator, in
  her words.
- **The human keeps the decision.** The coordinator confirms or changes every routing.
- **Tested with real women before each component is added.** The voice-first version was built and
  field-tested in Tunisia (32 sessions, 6% used voice); that is why the product looks the way it does.
- **Privacy by design.** Explicit consent in plain language, health data with a certified health-data
  host, no advertising, no sale of data.

## Stack

- PHP 8.3 / Laravel 13
- Spatie laravel-permission (roles: `admin`, `secretary`)
- Laravel Sanctum (API tokens)
- MySQL

## Getting started

1. Install PHP dependencies:

   ```bash
   composer install
   ```

2. Copy the environment file and generate the app key:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Configure your database credentials in `.env` (defaults to a local MySQL database named
   `luna_doctors_backend` — create it first if it doesn't exist:
   `mysql -u root -e "CREATE DATABASE IF NOT EXISTS luna_doctors_backend;"`).

4. Run migrations and seed the Secretary accounts/roles:

   ```bash
   php artisan migrate
   php artisan db:seed --class=SecretarySeeder
   ```

   > The seeded accounts use a fixed demo password and a non-production domain — intended for local
   > development only. See [Secretary accounts](#secretary-accounts-staff-api) below.

5. Serve the app:

   ```bash
   php artisan serve
   ```

   The app is now available at `http://localhost:8000` (API under `/api`, docs at `/docs/api`).

6. (Optional) Run a queue worker — needed for OCR text extraction on consultation documents to actually
   process:

   ```bash
   php artisan queue:work
   ```

The application needs no real patient data to run — see [Data protection](#data-protection).

## Secretary accounts (staff API)

`App\Models\Secretary` is a separate auth model from `users` — it's staff (admin/secretary), not
patients — gated by Spatie roles (`admin` or `secretary`). Staff authenticate with email+password (unlike
the passwordless `User` flow) and get a Sanctum token just like a `User` does. The `secretary` middleware
alias (`App\Http\Middleware\EnsureSecretary`) guards any route that must be called by staff, rejecting a
valid `User` token with a 403.

| Method | Endpoint | Notes |
| --- | --- | --- |
| POST | `/api/secretary/login` | `{email, password}`. Returns `secretary` + `access_token`. |
| POST | `/api/secretary/logout` | Requires `Authorization: Bearer <token>`. Revokes the current token. |
| POST | `/api/secretary/password/forgot` | `{email}`. Emails a 6-digit code, valid 30 minutes (404s if the email isn't a staff account). |
| POST | `/api/secretary/password/reset` | `{email, code, password, password_confirmation}`. Sets the new password and revokes every existing token for that account. |

Password reset follows the same code-by-email mechanic as the patient passwordless flow (own
`secretary_password_resets` table, mirroring `magic_codes`), just paired with a real password instead of
a passwordless login. In local (`APP_ENV=local`), the code is always `123456`.

`SecretarySeeder` (see step 4 above) creates the two roles and two **local-development-only** accounts —
do not reuse these credentials or domain in any deployed environment:

| Email | Password | Role |
| --- | --- | --- |
| admin@envast.tn | testtest | admin |
| secretary@envast.tn | testtest | secretary |

## API authentication (passwordless)

Auth model is the standard `users` table (`App\Models\User`), not `Secretary` — this is for API clients
(e.g. a doctor-facing app).

`users` has `first_name`/`last_name` (`last_name` is nullable, since the passwordless verify-code flow
auto-creates a user from just an email) and an `avatar_id` integer (default `1`, client-side avatar
catalog index — no server-side avatar model/table).

Flow: request a 6-digit code by email, then verify it to get a Sanctum token. No passwords involved.

| Method | Endpoint | Notes |
| --- | --- | --- |
| POST | `/api/auth/send-code` | `{email, type?: login\|signup}`. `type=login` 404s if the email isn't registered yet. |
| POST | `/api/auth/verify-code` | `{email, code}`. Verifies the code; auto-creates the user on first login. Returns `user` + `access_token`. |
| POST | `/api/auth/login` | `{email, code}`. Same as verify-code but 404s if the user doesn't exist yet. |
| POST | `/api/auth/register` | `{first_name, last_name, email, phone?, birthdate?, avatar?, user_id?}`. Creates a user, or updates one if `user_id` is passed. `birthdate` must be a past date (`YYYY-MM-DD`); `avatar` defaults to `1`. |
| POST | `/api/auth/logout` | Requires `Authorization: Bearer <token>`. Revokes the current token. |
| POST | `/api/oauth/signup/{provider}` | `provider` is `google` or `apple`. `{access_token, first_name?, last_name?, phone?, avatar?}`. Logs in if the email already has an account, otherwise creates one (201) — name overrides require both `first_name` and `last_name`, otherwise it's split from the provider's profile name (first word vs. the rest). |
| GET | `/api/gdpr-consent` | `?email=&type=login\|signup`. Returns the GDPR consent carousel plus that email's user profile (or `null`). |
| PUT | `/api/gdpr/accept` (auth:sanctum) | Marks the caller as having accepted GDPR terms (`has_accepted_gdpr`). |

In local (`APP_ENV=local`), the code is always `123456` to ease testing. Emails are sent via
`MAIL_MAILER` (defaults to `log`, i.e. written to `storage/logs/laravel.log` instead of actually sent).

`/api/oauth/signup/{provider}` needs real `GOOGLE_CLIENT_*` / `APPLE_CLIENT_*` credentials in `.env` to
actually verify a token against Google/Apple (left blank by default — calls will 400 until set).

### API docs

API documentation is auto-generated from the code (routes, form requests, resources) by
[Scramble](https://scramble.dedoc.co) — no manual OpenAPI YAML to maintain. It's live at:

- `http://localhost:8000/docs/api` — interactive UI (Stoplight Elements), includes a "Try it" panel.
- `http://localhost:8000/docs/api.json` — raw OpenAPI 3.1 spec.

Restricted to the local environment by default (`Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess`);
define a `viewApiDocs` gate to allow it elsewhere. New/changed routes, form requests, and resources are
picked up automatically — add a PHPDoc summary/description and
`#[Dedoc\Scramble\Attributes\Response(...)]` attributes on controller methods to document non-200
responses, since those can't be inferred from early `return`s.

## Consultation requests (OCR)

`POST /api/consultation-requests` (auth:sanctum) lets a signed-in user start booking an appointment: a
`reason`, optional `history`, and optional `documents[]` (pdf/jpg/jpeg/png, max 56 MB each,
`multipart/form-data`). Each uploaded document is stored **encrypted at rest** (`Crypt::encrypt`, under
`storage/app/private/public/consultation-documents`) and queued as an `ExtractConsultationDocumentOcr`
job — the response's `documents[].ocrText` is `null` until that job runs and updates it.

OCR text extraction calls the Mistral OCR API (`App\Services\Ocr\MistralOcrService`); set
`OCR_MISTRAL_API_KEY` in `.env` for it to actually work. A queue worker must be running for the job to
process:

```bash
php artisan queue:work
```

Each document in the response also carries a `url` — `GET /api/consultation-documents/{id}/download`
(`auth:sanctum`) — that decrypts and streams the original file. Since the file is encrypted at rest, this
is a real endpoint, not a plain storage URL. Available to the owning patient (their own documents only,
403 otherwise) and to any staff member (no ownership check, same as the rest of the staff-facing surface).

`GET /api/consultation-requests` (`auth:sanctum`) lists the caller's own requests, most recent first,
paginated (`per_page`, default 15, max 100). Every request has a `status`:

| Value | Meaning | Set when |
| --- | --- | --- |
| `new` | Not yet looked at by staff | Default, on creation |
| `reminder` | Staff has reached out, awaiting a callback | Set by staff |
| `validated` | Appointment confirmed | Set by staff |
| `treated` | Already handled | Set by staff |

Filter with `?status=new`, `?status=reminder`, `?status=validated`, or `?status=treated`, or omit it to
get everything. Only the authenticated user's own requests are ever returned — there's no cross-user
listing on the patient-facing endpoint.

For staff, `GET /api/secretary/consultation-requests` (`auth:sanctum` + `secretary`) lists requests
across **every** patient, same filter/pagination params, each item including the owning patient's profile
under `user` (the patient-facing endpoint above returns its own profile once at the top level instead,
since every item is already known to be theirs).

`PATCH /api/consultation-requests/{id}/status` (`auth:sanctum` + `secretary`) moves a request to a new
`status`. This is staff-only — a `User` (patient) token gets a 403 — and not scoped to a particular
patient, since staff need to triage everyone's requests: `{status: 'new' | 'reminder' | 'validated' |
'treated', status_reason?: string}`. `status_reason` is optional free text recording why the status
changed (e.g. "Patient confirmed by phone"); it reflects only the most recent change, not a history —
updating again without it clears it back to `null`.

## Development

```bash
npm install
npm run dev
```

## Testing

```bash
php artisan test
```

Tests run against a separate MySQL database (`luna_doctors_backend_testing`, configured in
`phpunit.xml`) rather than SQLite, since `pdo_sqlite` isn't installed in this environment. Create it once
with:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS luna_doctors_backend_testing;"
```

## Architecture

See [docs/architecture.md](docs/architecture.md) — client/API/storage diagram and where health data lives
and flows.

## Open-source software used

**Backend (this repository):**

| Layer | Software | Licence |
|---|---|---|
| Framework | Laravel 13 | MIT |
| Auth | Laravel Sanctum, Laravel Socialite | MIT |
| Authorization | spatie/laravel-permission | MIT |
| API docs | dedoc/scramble | MIT |
| Database | MySQL | — |
| Tests | PHPUnit, Mockery | BSD-3-Clause / MIT |
| Document extraction (OCR) | Mistral OCR API (external service, not bundled) | — |

**Frontend (`luna-patient`, direct dependencies):**

| Software | Licence |
|---|---|
| React, React DOM | MIT |
| React Router | MIT |
| React Hook Form, @hookform/resolvers | MIT |
| @tanstack/react-query | MIT |
| zod | MIT |
| radix-ui | MIT |
| class-variance-authority | Apache-2.0 |
| tailwind-merge, tw-animate-css | MIT |
| embla-carousel-react | MIT |
| lucide-react | ISC |
| shadcn | MIT |
| @fontsource-variable/geist (font) | OFL-1.1 |
| @amplitude/analytics-browser | MIT |

Both lists cover direct dependencies only. A complete, generated dependency tree with licences is
available via `composer licenses` (backend, 120 packages) and `npx license-checker` (frontend, 406
packages including transitive dependencies) — see
[docs/open-source-verification-report.md](docs/open-source-verification-report.md) for the full audit.

## Data protection

This platform processes health data. Explicit, informed consent before any processing; encrypted
storage; role-based access (a coordinator sees her centre's requests, a clinician sees the files routed
to them); hosting in France with Hosteur, certified health-data host (HDS) and ISO/IEC 27001:2022; audio
discarded after transcription; documents read on Luna's own infrastructure; no advertising, no sale of
data. A Privacy Impact Assessment on the UNICEF Venture Fund framework is maintained alongside this code,
and the platform is designed to GDPR standards and to the Tunisian personal-data law (2004-63, INPDP).

This repository contains **no data**: no patient records, no session data from field tests, no real
centre configuration.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). All contributors follow the
[Code of Conduct](CODE_OF_CONDUCT.md). Translations and deployment guides for new centres are especially
welcome.

## Licence

Apache License 2.0 — see [LICENSE](LICENSE).

## Acknowledgements

The clinical routing knowledge was captured with the coordinator and the medical lead, Dr
Jean-Philippe Estrade, of Centre Luna Marseille. Supported by the UNICEF Venture Fund. The views
expressed are those of Luna for Health and do not necessarily reflect those of UNICEF.
