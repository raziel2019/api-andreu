# Contact form email delivery

Implemented 2026-10-12 for Andreu Gurrera using installed Laravel 13.34.0, PHP 8.5.11, Laravel Mail and its existing Symfony SMTP transport. No new dependencies, migration, contact-submission database table, Vue modification or deployment.

## Audit and frontend boundary

The inspected frontend `ContactForm.vue` currently holds `{name, email, services: string[], message}`. Services are checkbox values from `page.service_options[].slug`, not a single text label. The widget exposes a `token`. Existing Vue calls only `POST /api/v1/contact/verify`, then prepares a mailto; it does **not** call the new submission endpoint yet. The standalone `services/contact.ts` `service` string belongs to that old mailto formatter, not a server-side submission contract.

The new endpoint preserves actual form state names (`services` plural) and the existing token name. Existing page/site Resources, CMS email/phone fields and `/contact/verify` responses remain compatible. Turnstile verification was extracted into `TurnstileVerification` so both endpoints share the same fail-closed implementation. No success from the old endpoint is cached or accepted as permission to send mail.

## Exact POST contract

`POST /api/v1/contact`, headers `Accept: application/json`, `Content-Type: application/json`, no cookies/credentials:

```json
{
  "name": "Visitant",
  "email": "visitor@example.test",
  "services": ["organization"],
  "message": "Voldria informació sobre el vostre servei.",
  "locale": "ca",
  "token": "<fresh token returned by the Turnstile widget>"
}
```

`organization` is an example only: use an active slug returned by `GET /api/v1/pages/contact`, not a hardcoded demo option. Accepted fields are exactly the six keys above. The endpoint rejects extra fields, including `to`, `recipient`, `cc`, `bcc`, `from`, `secret`, `remoteip`, `verified` and the obsolete singular `service` formatter field. Attachments are unsupported.

| Field | Validation |
| --- | --- |
| `name` | Required string, max 120 characters; no line/control characters or `<`/`>` |
| `email` | Required single RFC-valid email, max 254 characters; no whitespace/control characters |
| `services` | Required sequential list, 1–10 distinct slugs; each string max 100; only active options belonging to Contact page 1 |
| `message` | Required string, max 10,000 characters; normal tabs/newlines permitted, other controls rejected |
| `locale` | Optional `ca` or `es`, default CA; body locale takes precedence over the existing optional query locale |
| `token` | Required nonempty string max 2,048 characters; no whitespace/control characters |

Laravel's existing trimming/empty-string normalization applies. Message markup is treated as literal text and escaped, never executed. CA/ES are the only supported locales. Locale and validation errors return JSON; `Content-Language` accompanies endpoint responses. Invalid locale values receive the submission validation envelope, while older endpoints keep their existing locale-error shape.

### Response shapes

200 only after the synchronous SMTP send returns Laravel's `SentMessage` receipt:

```json
{
  "success": true,
  "code": "accepted",
  "message": "El servidor de correu ha acceptat el missatge per al lliurament."
}
```

A 200 means the configured SMTP server accepted the message. It does **not** prove delivery to the destination inbox, placement outside spam, or a later forwarding hop. No queue is used. A missing receipt, cancelled send or exception returns 503.

Validation 422:

```json
{
  "success": false,
  "code": "validation_failed",
  "message": "Revisa els camps del formulari.",
  "errors": { "email": ["<localized validation message>"] }
}
```

`errors` maps field paths to message arrays (`services.0`, `payload`, etc.). Other failures have exactly `{success:false, code, message}`:

| Status | Code | Meaning / retry |
| --- | --- | --- |
| 422 | `validation_failed` | Correct fields first; no Siteverify or Mail call |
| 422 | `turnstile_failed` | Cloudflare rejected token; obtain a fresh token |
| 429 | `rate_limited` | Wait for `Retry-After`; obtain a fresh token before resubmitting |
| 503 | `turnstile_unavailable` | Missing server secret, connection timeout, HTTP failure or malformed response; fail closed |
| 503 | `mail_unavailable` | Non-SMTP configuration, missing send receipt, invalid sender configuration or transport exception |

Messages are localized; use status/code/field paths for frontend mapping, not translated wording. Exceptions are caught even with debug enabled. SMTP exception text, upstream response bodies, credentials, token values and message bodies are not returned or explicitly logged by this implementation. Transport-failure logging includes only a fixed description and exception class. Do not enable request-body/SMTP-transcript logging in production infrastructure.

## Turnstile and abuse controls

Submission verifies its own token via Cloudflare Siteverify before sending mail. The secret comes only from `services.turnstile.secret_key`. Server-derived IP is forwarded; request body cannot set it. HTTP connect/total timeouts are 3/5 seconds, with TLS verification enabled and no automatic retry. Only literal boolean `success:true` is accepted. Every submission verifies again; an old `/contact/verify` success cannot bypass verification.

Cloudflare tokens expire after five minutes and are single-use. Calling `/contact/verify` and then `/contact` with the same token consumes it twice and fails. The final Vue flow must call **only `/contact`** once with that fresh token. See [Cloudflare server-side validation](https://developers.cloudflare.com/turnstile/get-started/server-side-validation/).

The `contact-submission` limiter counts attempts reaching the submission pipeline before Form Request validation: 5/minute and 20/hour per IP, plus 10/hour per normalized, SHA-256-hashed email across IPs. The existing verify limiter remains separate and unchanged. Laravel obtains IP through its configured trusted proxy policy; configure only the actual ingress proxies. Use a shared production cache for consistent quotas across workers/servers. No visitor message is stored in MariaDB; the recipient's mailbox retains delivered content under its retention policy.

This is a stateless public API without session credentials, so it does not use browser session CSRF tokens. Admin routes retain their normal CSRF protection. Existing CORS allows JSON POST only from the exact configured `FRONTEND_URL`, with no wildcard and no credentials. Set this to the real canonical Vue origin including scheme/port, without trailing slash. If the deployment intentionally serves multiple Vue origins, configure those exact entries in `config/cors.php`; do not use `*`. CORS is browser access control, not bot authentication; Turnstile and rate limits remain authoritative. Web servers should retain bounded request-body limits.

## Sender and recipient

- **To:** validated `SiteSetting` singleton 1 `contact_email`; absent/empty/invalid values fall back to `info@andreugurrera.cat`, configured once as `mail.contact_fallback_to`. CMS administrators may deliberately choose another valid recipient. To deliver to the requested mailbox, confirm that Identity and Contact contains that address or leave it empty for fallback. No CMS data was overwritten by this task.
- **From:** `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME`, using the authenticated mailbox/provider-authorized sender. Never the visitor's address.
- **Reply-To:** the visitor's validated email and name.
- **Content:** escaped visitor name/email, server-resolved localized selected service labels/slugs, message, UTC ISO-8601 submission time and locale. The email uses a safe Blade HTML view.

Only a configured mailer with `transport=smtp` is accepted for contact delivery. `log`, `array`, `failover` and other transports return 503 rather than a simulated success, even if a fallback driver would report a send. Read [Laravel Mail](https://laravel.com/framework/docs/13.x/mail) for the installed API conventions.

## IONOS production environment

IONOS Mexico's official documentation lists `smtp.ionos.mx`, port 465 with SSL/TLS or port 587 with STARTTLS. This is provider documentation, **not verification of this account's mailbox settings**. Confirm the actual outgoing hostname, mailbox identity and supported port in the IONOS account before configuring production. [IONOS client settings](https://www.ionos.mx/ayuda/e-mail/temas-generales/datos-necesarios-para-configurar-clientes-de-e-mail/).

Example only, replace the host from verified mailbox settings:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=<verified-IONOS-SMTP-host>
MAIL_PORT=587
MAIL_SCHEME=smtp
MAIL_REQUIRE_TLS=true
MAIL_TIMEOUT=10
MAIL_USERNAME=info@andreugurrera.cat
MAIL_PASSWORD=<secret-in-server-environment-only>
MAIL_FROM_ADDRESS=info@andreugurrera.cat
MAIL_FROM_NAME="Andreu Gurrera"
TURNSTILE_SECRET_KEY=<backend-only-widget-secret>
FRONTEND_URL=<exact-canonical-Vue-origin>
```

For port 465 use `MAIL_SCHEME=smtps`. Installed Laravel uses `MAIL_SCHEME`, not an obsolete `MAIL_ENCRYPTION` assumption; Symfony SMTP performs STARTTLS with `smtp`, and `MAIL_REQUIRE_TLS=true` refuses plaintext. Certificate verification stays enabled. No runtime/credential defaults were changed in the actual `.env`. `MAIL_URL`, if set, can override SMTP settings; remove stale DSN settings and never expose them in logs or diagnostics. Ensure SMTP username and From match the authenticated mailbox or an explicitly authorized alias.

After separately authorized production setup, rebuild Laravel configuration cache so workers use the new environment. Protect `.env`/secrets, use `APP_DEBUG=false`, HTTPS and server-controlled recipients. SMTP credentials are never frontend `VITE_` values. No queue worker is required for this synchronous send.

## Separate real local SMTP check

Docker already contains MailHog: SMTP service `mailhog:1025`, web UI host port 8025. Mailpit can be used equivalently with its actual configured service/port. Example local configuration:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_SCHEME=smtp
MAIL_REQUIRE_TLS=false
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS=info@andreugurrera.cat
MAIL_FROM_NAME="Andreu Gurrera"
MAIL_TIMEOUT=10
```

`false` TLS and null credentials are for the isolated local capture server only. Do not use these values for IONOS. A genuine local widget token still requires a local-allowed Cloudflare widget and backend secret when testing via HTTP manually.

An opt-in integration test sends one actual SMTP message to the existing local MailHog capture server:

```sh
docker compose exec -T -e CONTACT_SMTP_SMOKE_TEST=1 app php artisan test --compact tests/Feature/ContactSmtpIntegrationTest.php
```

This test uses isolated in-memory SQLite, test-only service data and a mocked Cloudflare response, **not `Mail::fake()`**. It overrides the mail host to local MailHog; no email leaves the capture server and no editorial data is inserted. It verifies actual SMTP acceptance through the new endpoint, not production Turnstile or external inbox deliverability. Inspect MailHog for `CONTACT_SMTP_SMOKE_TEST` and Reply-To `contact-smoke@example.test`. The normal full suite skips this network-dependent test unless opted in. No captured messages are deleted by the test.

## Real IONOS smoke test (not performed here)

1. Confirm account hostname/credentials and authenticated From identity; set CMS recipient to the intended `info@andreugurrera.cat`. Fix the DNS findings below before claiming deliverability.
2. Use the intended HTTPS staging/production origin with a real widget/secret. Obtain one fresh token, without calling `/contact/verify`.
3. Put the exact six-field JSON into a private temporary file, using an active service slug and an approved harmless smoke message. Do not include SMTP secrets. Submit via the frontend after its separately authorized integration, or:

```sh
curl --silent --show-error --fail-with-body \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  --data-binary @/private/tmp/contact-smoke.json \
  '<verified-backend-origin>/api/v1/contact'
```

4. Expect 200/`accepted` only after SMTP acceptance. Inspect the recipient mailbox (and spam), From, Reply-To, timestamp and CA/ES content. Confirm a reply targets the visitor, not the From mailbox. Track subsequent bounces independently.
5. To verify Gmail deliverability separately, send an approved IONOS-origin test to a Gmail mailbox you control, or validate the actual configured forwarding path with the account owner. Inspect “Show original”: SPF, DKIM and DMARC results plus aligned domains. Do not change this public endpoint into a client-selectable recipient relay for that test.
6. On failure, inspect provider delivery/bounce records with credentials redacted. A 503/time-out can be ambiguous if SMTP accepted DATA before the connection was lost; do not automatically retry the request. Obtain a fresh token and let the visitor deliberately retry after reviewing mailbox/provider evidence. Exactly-once inbox delivery is not claimed.

## DNS findings and SPF/DKIM/DMARC troubleshooting

Read-only public DNS checks on 2026-10-12, including both authoritative servers `cosmin.ns.cloudflare.com` and `laila.ns.cloudflare.com`, returned **two distinct SPF records at the domain apex**:

```text
v=spf1 include:_spf-us.ionos.com ~all
v=spf1 -all
```

The unrelated zone-ownership TXT is not an SPF record and should not be removed as part of SPF correction. Multiple `v=spf1` records cause SPF PermError; the previous duplicated-SPF problem is still present in this DNS snapshot. Keep one consolidated SPF policy that covers all legitimate senders after auditing them. IONOS Mexico documents `include:_spf-us.ionos.com` for externally managed DNS; do not add a second SPF record. [IONOS SPF guidance](https://www.ionos.mx/ayuda/dominios/configurar-un-servidor-de-email-y-registros-relativos/ionos-spf-para-que-tus-e-mails-lleguen-a-su-destino/), [SPF record selection](https://www.rfc-editor.org/rfc/rfc7208#section-4.5).

Current DMARC snapshot:

```text
v=DMARC1; p=reject; sp=reject; adkim=s; aspf=s; rua=mailto:info@andreugurrera.cat,mailto:arxiu@andreugurrera.cat
```

Strict alignment requires the authenticated identifier to exactly match the visible From domain for that mechanism. DMARC passes with a passing aligned SPF or DKIM result; the presence of a policy record alone is not a passing result. Diagnose the previous Gmail `550-5.7.26` from actual bounce and authentication headers. Do not claim that successful SMTP login fixes SPF/DKIM/DMARC or blindly weaken DMARC rejection.

MX currently resolves to `mx00.ionos.mx` and `mx01.ionos.mx`. DKIM CNAMEs `s1-ionos._domainkey` and `s2-ionos._domainkey` resolve to `s1.dkim.ionos.com` and `s2.dkim.ionos.com`. No CNAME answer was observed for the third selector `s42582890._domainkey` listed in current IONOS guidance. Confirm the account's actual signing selector and `d=` domain from a signed message; two published selectors do not prove active signing/alignment, and a missing alternative selector does not by itself prove DKIM failure. The authoritative DNS is Cloudflare, so account-side IONOS DNS changes alone will not alter this public zone. [IONOS DKIM instructions](https://www.ionos.mx/ayuda/dominios/configurar-un-servidor-de-email-y-registros-relativos/utilizar-la-autenticacion-de-e-mail-con-dkim/).

Repeat DNS checks after an authorized correction and propagation:

```sh
dig +short TXT andreugurrera.cat
dig +short TXT _dmarc.andreugurrera.cat
dig +short MX andreugurrera.cat
dig +short CNAME s1-ionos._domainkey.andreugurrera.cat
dig +short CNAME s2-ionos._domainkey.andreugurrera.cat
```

No DNS records were changed, no IONOS account configuration was accessed and no external SMTP/inbox smoke test was performed in this task.

## Pending Vue integration and deployment requirements

Vue is unchanged by explicit task constraint. Its Submit button will continue the old verify/mailto behavior until a separate frontend change:

- Post `name`, `email`, `services` slugs, `message`, current CA/ES locale and current widget `token` to `/api/v1/contact` only.
- Remove the verify-before-submit call and prepared-mailto success flow for this form. Do not require a public recipient setting in the browser; recipient choice is server-side.
- Show accepted feedback only on 200 with `success:true`/`code:accepted`; retain entered text on failures, map field errors and honor `Retry-After`.
- Reset the widget after every final submission attempt and obtain a fresh token before a deliberate retry. Do not reuse consumed/expired tokens or automatically retry SMTP timeouts. Allow a total client timeout covering 5-second Siteverify plus 10-second SMTP and network overhead.

Before a separately authorized production release: install existing locked dependencies, publish backend code, configure verified SMTP/From/Turnstile/canonical CORS origin, fix and verify DNS authentication, confirm outbound SMTP firewall connectivity and trusted proxies, rebuild configuration/route caches, coordinate the frontend change and perform the real smoke test. No deployment or dependency change occurred here.

## Validation performed

`ContactSubmissionTest` uses framework HTTP/Mail fakes for behavior, a mocked `SentMessage` receipt for accepted-response tests, and transport exception mocks. Fake-mail success is not evidence of real SMTP or inbox delivery. Tests cover To/From/Reply-To, fallback recipients, CA/ES, multiple active CMS services, required/invalid/bounded/unexpected fields, Turnstile failures/outages/reuse, absent receipts, SMTP exceptions/redacted logs, non-SMTP rejection, escaped HTML, IP/email quotas, stateless CSRF strategy and CORS. Existing `ContactVerificationTest` continues passing unchanged.

Separate local SMTP integration passed once against MailHog with actual SMTP and mocked Cloudflare. Production IONOS authentication and external deliverability remain unverified pending setup.


Final checks in PHP 8.5: complete Laravel suite **580 passed / 2,857 assertions**, with the one SMTP integration check skipped by default; opt-in real local SMTP check **1 passed / 3 assertions**. Pint `--dirty --format agent`, strict Composer validation, contact route inspection and diff whitespace checks passed. No migration is needed.
