# Contact Turnstile verification

## Backend configuration

Set `TURNSTILE_SECRET_KEY` in the backend environment to the Cloudflare widget secret. `.env.example` provides an empty placeholder. Laravel reads it through `services.turnstile.secret_key`; rebuild the deployment configuration cache with `php artisan config:cache --no-interaction` after changing it. An unset, empty or whitespace-only secret fails closed in every environment, including production.

Never put the secret in Vue, a `VITE_` variable, CMS content, public API responses or version control. The frontend widget uses Cloudflare's public site key. Configure the widget's permitted hostnames in Cloudflare and set `FRONTEND_URL` to the Vue origin for CORS.

## Request and response

Send `POST /api/v1/contact/verify` with `Content-Type: application/json` and `Accept: application/json`:

```json
{ "token": "<token returned by the Turnstile widget>" }
```

The token must be a nonempty string of at most 2048 characters. Optional `?locale=ca` or `?locale=es` follows the existing API locale contract, defaulting to Catalan. Unsupported locales return the existing safe JSON 422 locale error.

| Status | Body | Meaning |
| --- | --- | --- |
| 200 | `{ "success": true }` | Cloudflare verified the token |
| 422 | `{ "success": false }` | Missing/invalid token or Cloudflare rejected verification |
| 503 | `{ "success": false }` | Secret unavailable, Cloudflare HTTP/connection error, timeout or malformed response |
| 429 | `{ "success": false }` | Five attempts per minute per IP exceeded; observe `Retry-After` |

Only after HTTP 200 with boolean `success: true` should Vue prepare the existing mailto. On failure, do not prepare mailto; obtain a fresh widget token before another verification attempt. This backend change does not modify Vue. The endpoint sends no email, stores no message and grants no reusable authorization credential. A mailto remains a client-side action; verification does not control direct use of an email address.

## Server-side behavior

Laravel POSTs form-encoded `secret`, `response` and, when available, `remoteip` to `https://challenges.cloudflare.com/turnstile/v0/siteverify`. The IP comes from Laravel's request, never a client body field or a manually trusted forwarded header. If deployed behind a proxy, configure trusted proxies for the actual infrastructure so rate limiting and remote IP use the visitor address.

The HTTP client connects within three seconds and has a five-second total timeout. TLS verification stays enabled; no automatic retries consume a single-use token again. An HTTP-success response must contain a boolean `success`; only literal `true` is accepted. Upstream bodies, error codes, exception details, tokens and secrets are not returned or explicitly logged by this integration.

[Cloudflare's Siteverify documentation](https://developers.cloudflare.com/turnstile/get-started/server-side-validation/) specifies five-minute expiry and single-use tokens. Every verification calls Siteverify, so invalid, expired and reused tokens fail; success is not cached.

The named `contact-verification` limiter counts all attempts, including invalid input, before the controller runs. CORS permits POST from the existing `FRONTEND_URL` origin without credentials; content routes remain read-only.

## Tests

`tests/Feature/ContactVerificationTest.php` mocks all Cloudflare calls and prevents stray network requests. It covers successful payload forwarding, failed/expired verification, token reuse, missing/invalid/oversized input, missing secret in production, connection failures/timeouts, upstream HTTP errors, malformed/nonboolean responses, IP rate limiting and expiry, and Vue POST preflight.

Run `php artisan test`, `vendor/bin/pint --dirty --format agent` and `composer validate`.
