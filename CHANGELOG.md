# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- JSON REST API under `/api` with bearer-token authentication and a consistent response format
  (`status`, `message`, `data`); errors are always returned as JSON, never as a redirect to a login page.
- Interactive API documentation at `/docs/api` and the OpenAPI 3.1 spec at `/docs/api.json`, generated
  from the code (local environment only by default).
- Passwordless sign-in for patients: request a 6-digit code by email, then exchange it for an access
  token. The code is valid for 30 minutes, and an account is created automatically on first sign-in.
- Patients can create or update their profile: first and last name, email, phone, birthdate, location
  and avatar.
- Signing out revokes the current access token.
- Authentication messages in English and French.
- Sign up or log in with a Google or Apple account. An existing account with the same email is reused
  instead of being duplicated, and Apple private-relay addresses are recognised.
- GDPR consent screens in plain language, in English and French, returned together with the profile of
  the account that uses that email, if one exists.
- Patients can record that they accepted the GDPR terms.
- Onboarding screens that explain how to prepare an appointment at the Luna centre, in English and
  French, with illustrations and intro videos.
- Patients can complete onboarding and update their name, phone and avatar in the same step.
- Staff (secretariat) accounts, separate from patient accounts, with `admin` and `secretary` roles, plus
  a seeder that creates local demo accounts.
- Staff log in and out with email and password.
- Staff can reset a forgotten password with a 6-digit code sent by email; a reset signs the account out
  on every device.
- Staff-only endpoints refuse patient tokens (403).
- Transactional emails through Brevo, sent immediately or queued with automatic retries (up to
  3 attempts); recipients and message bodies are kept out of the logs.
- `php artisan brevo:test {email}` command to check the Brevo setup.
