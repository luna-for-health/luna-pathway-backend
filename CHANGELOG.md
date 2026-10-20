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
