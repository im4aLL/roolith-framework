# Security

Roolith ships secure by default and stays quiet about it. Baseline headers are on, hosts are allowlisted, proxy headers are untrusted until you opt in, state-changing routes can require CSRF tokens, uploads are validated by content (not by name), and sensitive files return 404 over HTTP.

This page is the central reference. Feature pages link here instead of repeating the rules.

## Security headers

`App\Core\System::bootstrap()` calls `sendSecurityHeaders()` on every request. Values are conservative and fail closed. Headers never break the request: failures are swallowed, and sending is skipped when headers were already sent.

| Header | Value | What it does |
| --- | --- | --- |
| `Content-Security-Policy` | `default-src 'self'` | Scripts, styles, frames, and other resources must come from your own origin. Inline scripts and remote hosts are blocked. |
| `X-Content-Type-Options` | `nosniff` | Browsers must respect the declared `Content-Type` and must not sniff responses into scripts. |
| `X-Frame-Options` | `SAMEORIGIN` | Pages can only be framed by pages from the same origin. Blocks basic clickjacking. |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Same-origin requests send the full URL. Cross-origin requests send only the origin on https, and nothing on https-to-http downgrades. |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` | Pins https for one year including subdomains. Sent only on https requests because browsers ignore it over plain http. |

The `default-src 'self'` policy blocks inline `<script>` and `<style>` blocks and remote hosts such as a Vite dev server. When you need inline code or Vite HMR, extend or override the policy in your reverse proxy or view layer.

### Turning headers off

Headers default to on. Turn them off only when a reverse proxy already sends equivalent headers.

```text
# .env
SECURITY_HEADERS=0
```

Precedence is simple: boolean `securityHeaders` in `config/config.php` wins when present, otherwise `SECURITY_HEADERS` in `.env` is parsed with `FILTER_VALIDATE_BOOLEAN`, otherwise headers stay on. See `config/config.md` for the override shape.

## Host allowlist and safe redirects

The allowlist is derived from your `baseUrl` (`APP_URL` in `.env`). It contains the base host plus its `www` or bare counterpart, lowercased and without the port, so `localhost:8080` still matches a `localhost` base URL and `example.com` matches `www.example.com`.

| Input | Result |
| --- | --- |
| `Host` matches the allowlist | Used for canonical redirects and URL building. |
| `Host` does not match | Logged as `host mismatch dropped`, no redirect is sent, the spoofed host never leaks into a `Location` header. |
| No `Host` (CLI, tests) or unparsable `baseUrl` | Checks return permissive so bootstrap stays runnable outside a web request. |

Canonical hosts are enforced with a permanent `301` via `PreProcessor::forceNonWww()` or `forceWww()` depending on `FORCE_NON_WWW`. There is no off mode: one canonical host always wins. `Host` values are CR/LF-stripped before comparison to block header injection.

```text
# .env
APP_URL=https://example.com/
FORCE_NON_WWW=1
```

`Request::fullUrl()` uses the same allowlist. When the `Host` header fails validation it logs a warning and falls back to the configured base host (scheme plus host plus port from `baseUrl`), or to `localhost` when no base URL is configured. `Request::url()` is `fullUrl()` without the query string.

Redirect targets go through `PreProcessor::resolveSafeRedirectTarget()`, which is also what `redirect()` and `Request::redirect()` use:

- Single-slash relative URLs such as `/dashboard` are allowed.
- Absolute `http` or `https` URLs are allowed only when the host is allowlisted.
- Everything else (`//evil.com`, `/\evil`, `javascript:...`, unknown hosts, empty input) falls back to `/`.
- CR/LF is stripped first, the path is percent-encoded per segment, and mismatches are logged.

The global `redirect()` helper defaults to `303` for safe Post/Redirect/Get. `Request::redirect()` keeps a legacy `302` default. Both are no-exit: return the `Response` from your controller. See [Request](/request) for input and URL helpers.

## Trusted proxies and client IP

Proxy headers are untrusted by default. `getIpAddress()` returns `REMOTE_ADDR` unless that address itself is a trusted proxy, so clients cannot spoof rate limiting or logs by sending `X-Forwarded-For` directly.

Set trusted proxies as a comma-separated list of exact IPs:

```text
# .env
TRUSTED_PROXIES=10.0.0.1, 10.0.0.2
```

| Rule | Detail |
| --- | --- |
| Format | Comma-separated IPs, whitespace is trimmed. |
| Match | Exact IP match only against `REMOTE_ADDR`. |
| CIDR | Not supported. Ranges are dropped silently, list individual proxy IPs. |
| Invalid entries | Filtered out with `FILTER_VALIDATE_IP`. |
| Default | Empty means trust none. Proxy headers are never honored. |

When `REMOTE_ADDR` is trusted, `getIpAddress()` checks these headers in order and returns the first valid IP (first entry before any comma): `HTTP_CLIENT_IP`, `HTTP_X_FORWARDED_FOR`, `HTTP_X_FORWARDED`, `HTTP_FORWARDED_FOR`, `HTTP_FORWARDED`. Otherwise it returns `REMOTE_ADDR`. Only list proxies you control, such as your load balancer. Invalid or missing `REMOTE_ADDR` falls back to `127.0.0.1`.

## CSRF protection

Tokens are per session. `Csrf::token()` generates one 64-char hex token from `random_bytes(32)`, stores it in the session under `_csrf_token`, and reuses it until rotation. `Csrf::validate()` compares with `hash_equals()`. `Csrf::rotate()` issues a fresh token, call it on privilege changes such as login. All entry points start the session so cookie flags stay consistent.

| Helper | What it does |
| --- | --- |
| `csrf_token()` | Returns the current session token string. |
| `csrf_field()` | Renders `<input type="hidden" name="_csrf" value="...">` for HTML forms. |
| `X-CSRF-TOKEN` header | Alternative for fetch or XHR clients. `X-XSRF-TOKEN` is also accepted. |
| `Csrf::tokenFromRequest()` | Reads the `_csrf` POST field first, then the `X-CSRF-TOKEN` and `X-XSRF-TOKEN` headers. |
| `Csrf::rotate()` | Clears the session token and returns a fresh one. |

`App\Middlewares\CsrfMiddleware` enforces the token. Safe methods (`GET`, `HEAD`, `OPTIONS`) pass through untouched. `POST`, `PUT`, `PATCH`, and `DELETE` require a valid token or the request stops with a `403 Invalid CSRF token.` response before your controller runs. Successful checks add an `X-CSRF-Validated: 1` marker header.

```php
$router->post('/form', [FormController::class, 'submit'])->middleware(CsrfMiddleware::class);
```

Every protected form needs the hidden field:

```html
<form method="POST" action="/form">
    <?= csrf_field() ?>
    <button type="submit">Submit</button>
</form>
```

Fetch clients send the header instead:

```js
fetch('/form', {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': token },
    body: formData
});
```

See [Middleware](/middleware) for attaching middleware and [Views](/views) for form rendering.

## File upload hardening

`App\Core\File` validates content, not just the file name. The full flow with examples lives in [File Upload](/file-upload). The security-relevant checks are:

- MIME allowlist: detected with `finfo` (`FILEINFO_MIME_TYPE`) with a `mime_content_type` fallback, compared exactly per extension after lowercasing and stripping parameters. `docx`, `xlsx`, and `pptx` are exact-match only: files that detect as `application/zip` are rejected by design.
- Double-extension guard: every dot segment except the final extension is checked against an executable deny list (`php`, `phtml`, `phar`, `php3`-`php8`, `cgi`, `pl`, `py`, `sh`, `exe`, `asp`, `aspx`, `jsp`, `htaccess`, and similar). `shell.php.jpg` is rejected. Single-extension names pass.
- Random stored name: `16` hex chars from `random_bytes(8)` plus the validated extension, with no user-supplied fragment. A custom name is accepted only after strict sanitizing (no separators, no `..`, allowlisted extension plus MIME plus executable-segment checks), otherwise the upload falls back to a random name.
- Fail-closed ordering: `upload()` validates first so rejected files never create directories, then creates the destination plus deny-execution protection, then moves the file. Size defaults to `5 MB` capped by `upload_max_filesize`.

Prefer storing private files in `storage/` outside the docroot. The `.htaccess` protection below is defense in depth for web-accessible directories.

## Apache and .htaccess hardening

The root `.htaccess` hides sensitive paths and routes clean URLs to the front controller. Sensitive matches return `404` (not `403`) so file existence is hidden.

| Rule | Matches | Effect |
| --- | --- | --- |
| `RedirectMatch 404 "(?i)(^|/)(config\|vendor\|\.git)(/\|$)"` | `config/`, `vendor/`, `.git` paths | Returns 404, keeps source and history out of HTTP reach. |
| `RedirectMatch 404 "(?i)(^|/)(constant\.php\|cms-constant\.php\|installer\.zip\|composer\.json\|composer\.lock\|phpunit\.xml\|\.env.*\|.*\.log(\..*)?)$"` | Constants, installer archive, Composer manifests, PHPUnit config, any `.env` variant, any `.log` file | Returns 404, hides config and secrets. |
| `RewriteRule ^(.*)$ index.php/$1 [L]` | Any request that is not an existing file or directory | Front-controller rewrite for clean URLs. |

`public/uploads/.htaccess` makes uploads inert even if an attacker lands a script there:

- `php_flag engine off` inside `mod_php`, plus `RemoveHandler` and `RemoveType` for `.php` and `.phtml`.
- `<FilesMatch>` denies script extensions (`php`, `phtml`, `phar`, `phps`, `cgi`, `pl`, `py`, `sh`) and dotfiles.
- `FS::protectUploadDirectory()` writes equivalent deny-execution protection into every `File::upload()` destination and leaves an existing `.htaccess` untouched so deployer customizations survive.

The Docker image adds server-layer hardening (`ServerTokens Prod`, `ServerSignature Off`, `TraceEnable Off`, `FileETag None`) with expires and deflate for static assets only. TLS is terminated outside the container. See [Docker](/docker) for the compose setup.

## Quick checklist

- Set `APP_URL` to your canonical https URL and keep `FORCE_NON_WWW` intentional.
- Leave `SECURITY_HEADERS` on unless your proxy sends the same headers.
- Set `TRUSTED_PROXIES` only to proxy IPs you control, as exact IPs.
- Attach `CsrfMiddleware` to every state-changing route and include `csrf_field()` in every HTML form.
- Validate uploads with `isValid()` before `upload()`, store private files outside the docroot.
