# Contributing to Luna for Health Tunisia

Thank you for considering a contribution. This project is open source so that any medical centre, NGO or
ministry can deploy a structured intake-and-routing pathway for women's health. Contributions that make
it easier to deploy, translate, or adapt to a new centre are especially welcome.

## Ground rules

- Be kind. The [Code of Conduct](CODE_OF_CONDUCT.md) applies everywhere in this project.
- Never commit data. No patient data, no real session data, no real centre configuration, no secrets or
  credentials. Use the fictional demo centre.
- Health context. This software routes women to care; it must never present itself as diagnosing.
  Wording in the interface is reviewed with that in mind.

## How to contribute

### Report a bug or propose a feature

Open an issue using the matching template. For a bug: what you did, what you expected, what happened,
version or commit. For a feature: the problem it solves for a woman, a coordinator or a clinician — not
only the solution.

Security vulnerabilities: do **not** open a public issue. E-mail <!-- fill: security@<domain> --> and we
will answer within 5 working days.

### Make a change

1. Fork the repository and create a branch from `main`: `feat/<short-name>`, `fix/<short-name>` or
   `docs/<short-name>`.
2. Make your change. Add or update tests. Keep the change focused: one purpose per pull request.
3. Run the test suite locally (`composer test`) and the linter (`vendor/bin/pint`).
4. Open a pull request against `main`. Fill in the template: what changed, why, how it was tested,
   screenshots for interface changes.
5. A maintainer reviews within 5 working days. We may ask for changes; we may also merge with small
   edits.

No direct pushes to `main`, including by maintainers. Every change goes through a pull request.

### Translations

Interface strings for the backend live in `lang/`. French is the reference and only maintained language.
A new language needs a native reviewer for the consent screens — consent has to be understood to be
valid.

### Adding or adapting a centre

A centre is configured through the centre-configuration tool, not in code. If your adaptation needs a
change in the routing engine itself, open an issue first so we can discuss the general case.

## Commit messages

Short imperative subject line (≤ 72 characters), a blank line, then the why. Reference the issue:
`Fixes #12`.

## Licence of contributions

By contributing you agree that your contribution is licensed under the project's licence (see
[LICENSE](LICENSE)).

## Maintainers

Luna for Health — <!-- fill: names and contact -->
