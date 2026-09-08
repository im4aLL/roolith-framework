# Security

## Reporting

Email `me@habibhadi.com` with steps to reproduce, impacted versions, and a severity estimate. Expect an acknowledgement within 72 hours. Do not open a public issue for unpatched vulnerabilities.

## Support matrix

- Supported: latest `main` plus the most recent tagged release.
- PHP: `>=8.2` with `intl`, `zip`, `opcache` (see `Dockerfile` and `composer check-platform-reqs`).
- Run `composer audit` regularly; version constraints use caret (`^`) deliberately so patches flow without pinning exact versions unless documented.

## Hardening notes

- Production defaults are fail-closed (`APP_ENV` defaults to `production`, Whoops only in `development`).
- Redirects use an allowlist (`PreProcessor::resolveSafeRedirectTarget()`), uploads validate MIME, cookies default to `Lax` plus `Secure` on https.
