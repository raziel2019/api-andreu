# Portfolio CMS — Phases 1 and 2

## Architecture and package inventory

Laravel 13.34 is the editable content source of truth for the independent Vue application. MariaDB persists content; public JSON endpoints require no session. Blade renders the session-authenticated CMS. Redis and Nginx remain the existing infrastructure. There is no Blog, registration, project-detail schema, content API write endpoint, or contact submission/email endpoint. Contact has a dedicated server-side Turnstile verification endpoint.

Reused packages: Laravel's session guard, Gate authorization, Eloquent, Form Requests, API Resources and Flysystem; PHPUnit 12.5.37, Laravel Pao 1.1.5 and Pint 1.32.1 for checks. Boost 2.10.1 was installed by the user before implementation. This phase adds no Composer or npm dependency. No Livewire, Filament or auth starter kit was previously installed or added. The Vue repository was inspected for approved content and asset references, and was not modified.

## Authentication

- `GET /admin/login`: login form; `POST /admin/login`: credential check.
- `POST /admin/logout`: logout, invalidate session, regenerate CSRF token.
- `/admin` and every CMS screen require the existing `web` guard plus the `access-admin` Gate. `users.is_admin` defaults to false and is excluded from user mass assignment.
- Login requires valid credentials **and** `is_admin = true`; regenerate the session on success. Non-administrators receive 403 on CMS routes even with an authenticated session. Guests redirect to the admin login.
- All state-changing admin forms run in the `web` middleware group and include CSRF tokens. Logout is POST only. Passwords use the existing Laravel `hashed` cast and configured bcrypt work factor.
- Login limits: five attempts per minute per normalized email/IP pair plus thirty per IP. There are no registration or password-reset screens in Phase 1.
- `ADMIN_EMAIL` and `ADMIN_PASSWORD` are optional development seed variables. Set both; password must have at least twelve characters. `AdminUserSeeder` runs only in local/testing, promotes the specified account, and does not reset an existing account's password on reseed. No credentials or demo user are hardcoded. Provision production administrators separately through a controlled deployment process.

## Schema and relationships

Three new migrations create the domain tables and add `users.is_admin`; existing user/session/cache/job migrations are retained. Singleton pages and settings are addressed by ID 1; they have no public creation/deletion route.

| Model / table | Content and relationships |
| --- | --- |
| `User` / `users` | Existing account, hashed password, new admin authorization flag |
| `SiteSetting` / `site_settings` | Contact email, phone, translated location, social URLs, logo storage reference, shared callout/footer copy and destinations |
| `HomePage` / `home_pages` | Translated hero paragraphs, eyebrow, image description/caption, hero CTA, summaries headings, portfolio teaser, CTA copy and destinations; has many summaries |
| `HomeServiceSummary` / `home_service_summaries` | Stable slug, translated title/description, optional icon reference, order; belongs to Home |
| `AboutPage` / `about_pages` | Title, introduction, image/caption, benefits and method headings/introduction; has many benefits and steps |
| `AboutBenefit` / `about_benefits` | Translated content and order; belongs to About |
| `WorkingMethodStep` / `working_method_steps` | Translated content and order; belongs to About |
| `ServicePage` / `service_pages` | Current Services page heading, subtitle, lead, introduction, image and CTA; separate from repeatable services |
| `Service` / `services` | Stable slug, translated title, nullable subtitle, description, optional icon, active flag, order; has many features |
| `ServiceFeature` / `service_features` | Translated content and order; belongs to Service |
| `PortfolioPage` / `portfolio_pages` | Under-construction flag, translated title/status/message/CTA, internal CTA destination and nullable LinkedIn destination; no projects |
| `ContactPage` / `contact_pages` | Translated title, introduction, content, conversation and service legend; has many options |
| `ContactServiceOption` / `contact_service_options` | Stable slug, translated label, active flag, order; belongs to Contact |

All new content tables have timestamps. Foreign keys cascade deletion of dependent repeatable content. Active/order and parent/order indexes support actual API queries. Relationships order by `sort_order`, then `id` for deterministic ties. There is no generic key/value CMS table.

## Translation and locale behavior

Editorial translation columns use JSON objects with `ca` and `es` string values, cast to arrays. Catalan is canonical and is fixed as the application/default/fallback locale; environment overrides cannot introduce another editorial language. Paragraphs are plain strings separated by two newlines, preserving approved wording and hierarchy.

Every public endpoint accepts `?locale=ca` or `?locale=es`, defaults to `ca` when omitted, and sends `Content-Language`. Unsupported, empty or array locale values return JSON 422. Missing or null Spanish translations fall back to Catalan. Nullable untranslated fields return null. API Resources select localized strings; they never expose the raw translation object. Admin fields show Català and Castellano separately. The admin interface uses Catalan, with the requested navigation labels.

## Admin sections

Navigation: Inicio, Sobre mi, Serveis, Portafoli, Contacte, Configuració.

- `/admin`: initial dashboard with section navigation and service count.
- `/admin/home`: complete Home editor; translated copy, destination validation, image preview/upload/removal, and add/edit/remove/reorder service summaries. Changes immediately appear in the public Home API. `PUT /admin/home` is the save action. Summary identifiers are stable lowercase slugs; maximum twelve summaries.
- `/admin/settings`: contact email, phone, CA/ES location, HTTP/HTTPS social URLs, logo upload/removal and translated shared callout/footer copy with destinations. Empty contact values remain allowed. `PUT /admin/settings` saves changes.
- `/admin/about`: full copy/image editor and add/edit/remove/reorder benefits and working-method steps.
- `/admin/services`: service list and bulk ordering; create/edit/delete services, visibility and repeatable features. `/admin/services/page` edits the Services page introduction, image/accessibility copy and CTA.
- `/admin/portfolio`: construction-only page copy, under-construction flag and optional CTA/LinkedIn destinations. Project management is explicitly outside scope.
- `/admin/contact`: complete page copy plus contact-option list/order controls; `/admin/contact/options/create` and per-option edit screens manage bilingual labels, visibility and confirmed deletion.

Phase 2 reuses all Phase 1 models, relationships, migrations, approved seeds, auth and API Resources. No packages or environment variables were added. See [Phase 2 implementation](cms-phase-two.md) for forms, file inventory and verification.

Form Requests authorize writes and validate translated structures, required CA/ES values, lengths, emails, URLs, booleans, order and uploads. Controllers coordinate requests and responses. `UpdateHomePage` handles filesystem changes and transactional persistence; it preserves shared media references and removes new files on a failed database transaction. Only validated, intended fields are persisted. Blade escapes editorial text.

## Media

MariaDB stores paths only. Assets live on Laravel's existing `public` filesystem disk under `storage/app/public`, served through `public/storage`. Run `php artisan storage:link` after setup. Resources convert references to URLs using that disk; configure `APP_URL` for the backend origin.

Approved current assets are bundled under `database/seeders/media` and copied to `seed/` by the content seeder when missing. They include the existing logo, Home/About/document images, three summary icons and four service icons. Trusted source SVG icons are seed assets only; uploaded SVG is rejected.

Home, About, Services page, service icon and logo uploads accept JPG/JPEG, PNG and WebP, checking image contents, MIME-derived type, filename extension, 5 MB size and maximum 6000 × 6000 dimensions. Laravel generates storage filenames. Replacing/removing an image deletes an old file only when no CMS model still references it. Nginx serves `/storage/` as static files, preventing PHP execution, and sets `nosniff`. Docker PHP and Nginx upload limits allow the two-image form; rebuilding `app` applies the PHP configuration change. Existing images stay unchanged after invalid submissions.

`CmsMedia` centralizes generated-file storage and reference-aware cleanup. `SaveContent` wraps persistence and child synchronization in a transaction, cleaning up newly uploaded files on failure and checking old references after commit. Existing Home persistence reuses the same cleanup service. A general media library, transformations and unused-file maintenance remain optional future work.

## Public API and frontend contract

Content endpoints are GET/HEAD only; content writes return 405. `POST /api/v1/contact/verify` validates a Turnstile token without storing a submission or sending email. API routes use Laravel's stateless API middleware, not admin sessions, and errors render JSON.

| Endpoint | Response |
| --- | --- |
| `/api/v1/site` | `{ "data": { ... } }`: localized settings/shared content and nullable contact/social/media fields |
| `/api/v1/pages/home` | `{ "data": { ... } }`: localized Home copy, URLs/destinations, ordered `service_summaries` |
| `/api/v1/pages/about` | `{ "data": { ... } }`: localized copy, ordered `benefits` and `working_method_steps` |
| `/api/v1/pages/portfolio` | `{ "data": { ... } }`: construction state/copy, optional CTA/LinkedIn; no project details |
| `POST /api/v1/contact/verify` | `{ "success": true }` only after server-side Cloudflare verification; safe failures and five attempts/minute/IP |
| `/api/v1/pages/contact` | `{ "data": { ... } }`: localized copy and active, ordered `service_options` |
| `/api/v1/services` | `{ "data": [ ... ], "meta": { "locale": "ca", "page": { ... } } }`: active ordered services with ordered `features`; localized Services page editorial copy in `meta.page` |

Fields use snake_case. Every record includes `id`; repeatable items include `sort_order`, and services/options/summaries include their stable `slug`. Each translated field resolves to a plain string or null. Media path fields are exposed as `*_url`, not database/storage paths. For example `hero_image_path` becomes `hero_image_url`, and `icon_path` becomes `icon_url`.

Home includes `hero_title`, `hero_content`, `hero_eyebrow`, `hero_image_url`, `hero_image_alt`, `hero_image_caption`, `hero_cta_label`, `hero_cta_destination`, `services_heading`, `services_introduction`, `service_summaries`, `stories_heading`, `stories_introduction`, `portfolio_title`, `portfolio_image_url`, `portfolio_image_alt`, `portfolio_cta_label`, `portfolio_cta_destination`, `cta_title`, `cta_content`, `cta_label`, `cta_destination`. Summaries have `title`, `description` and `icon_url`.

About children expose localized `content`. Services expose `title`, nullable `subtitle`, `description`, `icon_url`, `active`, `features`. Contact options expose `slug`, `label`, `active`. Portfolio's `linkedin_destination` is null until confirmed; `cta_destination` supports the current internal Home link.

Vue should fetch the backend origin plus these paths, pass its selected locale, read `data`, and handle null contact/social values without inventing links. Split paragraph strings on `\n\n` to preserve the current paragraph layout. Render content as text, not HTML. Internal CTA destinations match existing Vue routes (`/sobre-mi`, `/servicios`, `/portfolio`, `/contacto`). IDs/slugs remain unchanged when switching locale. Stable contact-option slugs match current Vue form choices. UI-only form validation/button labels may remain in Vue's localization resources; editorial copy comes from the API. This phase does not modify or wire up Vue.

CORS applies only to `/api/v1/*`, allows GET/HEAD/POST/OPTIONS, exposes `Content-Language`, and uses `FRONTEND_URL` as the allowed origin. No credentials are needed or allowed. The default example is the inspected local Vue origin, `http://localhost:5173`; production domains are not hardcoded.

## Seed strategy

`ApprovedContentSeeder` reads the repository-owned snapshot `database/seeders/content/approved-content.json`, copied from the current Vue `src/locales/ca.ts` and `es.ts`. The Catalan About/Services text matches `Frontend/docs/client-revision-01.md`; Spanish is the current complete translation. No obsolete design copy is used. Seed image mappings follow current Vue components, not old Figma manifests.

Fresh seeding provides Home (including three summaries), About (three benefits and three steps), four Services (twelve features plus Services page editorial content), construction-only Portfolio, Contact (six approved options), and site settings. Contact location is the existing approved location. Email, phone, LinkedIn and Instagram remain null. Reseeding preserves existing page/editor changes and existing service values, and does not reset an existing administrator password. Child collections seed when their parent is first created; the seeder is not a content synchronization command.

## Environment and Docker verification

New environment variables: `FRONTEND_URL`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`. `.env.example` declares them and uses CA for application/fallback locale and ES for Faker. Existing `APP_URL`, `APP_KEY`, database/session/cache variables and optional filesystem settings retain their roles. The current Compose database uses `DB_CONNECTION=mysql`, `DB_HOST=db`, `DB_PORT=3306`, `DB_DATABASE=laravel`; Laravel's mysql driver supports the existing MariaDB service. Use HTTPS, `APP_DEBUG=false` and secure session cookies in production.

From the backend directory, with Docker running and socket access available:

```sh
docker compose up -d --build app db redis nginx
docker compose exec -T app php artisan migrate:fresh --seed
docker compose exec -T app php artisan storage:link
docker compose exec -T app php artisan test --compact
docker compose exec -T app php artisan route:list --except-vendor
docker compose exec -T app composer validate
docker compose exec -T app vendor/bin/pint --format agent
```

`migrate:fresh` drops the configured database; use it only for the requested fresh development setup. Existing PHPUnit configuration uses isolated SQLite in-memory databases; the fresh-seed command verifies the MariaDB schema through the actual `app`/`db` services. For a separate MariaDB test run, use a dedicated test database and override PHPUnit DB settings; never run database-refresh tests against content to retain.

## Remaining work and execution status

All current editorial CMS sections are editable after Phase 2. Optional future work includes a media library/transformations, production administrator provisioning or password recovery, and separate Vue API integration. Portfolio project details, SEO and Blog were not implemented. Nothing was deployed and the Vue repository was not modified.

Final Phase 2 Docker verification: MariaDB `migrate:fresh --seed` passed; PHPUnit passed **201 tests / 754 assertions** (92 added beyond Phase 1); full-project Pint, Composer validation, route listing and Nginx validation passed. Live Nginx requests returned HTTP 200 for admin login and all six public endpoints. Authenticated editor flows are verified by HTTP feature tests, not an interactive browser session. Tests use isolated SQLite in memory; the migration/seed command exercised the actual MariaDB service. No Git metadata is present, so full-project Pint was used rather than `--dirty`.

## Phase 3 — Legal / GDPR content

LegalPage adds a dedicated `legal_pages` table with unique constrained types (`legal_notice`, `privacy_policy`, `cookie_policy`), JSON CA/ES title/body, inactive-by-default visibility and timestamps. The three `/admin/legal/{section}` editors reuse authenticated admin authorization, bilingual plain-text fields, Form Requests and SaveContent. Public GET `/api/v1/legal/legal-notice`, `/api/v1/legal/privacy` and `/api/v1/legal/cookies` use localized Resources and existing locale middleware. Inactive/missing pages return 404; activation requires both languages. Seeds provide titles and null draft bodies and preserve edits.

SiteSetting adds nullable, admin-only legal/business name, tax identifier, postal address, separate privacy contact, professional registration and distinct controller identity. Existing contact email/phone are reused. Facts are never automatically invented or interpolated into editorial content. Full editing, publication, client-confirmation and frontend rendering/consent contracts are in [legal-cms.md](legal-cms.md). No Terms, Blog, Vue changes, cookie banner, SEO or deployment is included.

## Contact verification

`POST /api/v1/contact/verify` accepts `{ "token": "<Turnstile token>" }`. Laravel validates the input and POSTs the token, backend-only `TURNSTILE_SECRET_KEY` and request IP to Cloudflare Siteverify through the Laravel HTTP client. Only an HTTP-success response containing boolean `success: true` permits the frontend to prepare its existing mailto. Tokens are neither cached nor accepted locally: Cloudflare rejects invalid, expired and reused tokens. No email is sent and no contact submission is persisted.

The endpoint limits every attempt to five per minute per IP. Missing configuration, upstream errors/timeouts and malformed responses fail closed with 503; invalid input or failed verification returns 422; throttling returns 429. Responses contain only a success boolean. Connection/total timeouts are three/five seconds, TLS verification remains enabled, and single-use validation is not automatically retried. See [Turnstile setup and contract](turnstile.md).

## Central page visibility

`/admin/pages/visibility` provides session-authenticated, Gate-authorized controls for Home, About, Services, Portfolio, Contact, Legal Notice, Privacy and Cookies. A new migration adds default-true boolean `active` to the five singleton page models; LegalPage reuses its existing field. Hidden pages remain editable and retain all editorial content, child records and media. Legal publication validation is preserved.

Public page endpoints return 404 when hidden, including the complete Services response. Home never redirects. `/api/v1/site` adds `data.pages`, an eight-key boolean map (`home`, `about`, `services`, `portfolio`, `contact`, `legal_notice`, `privacy`, `cookies`) for public navigation/footer integration. Missing records report false. Reseeding preserves visibility choices; legal seeds remain inactive drafts. See [page visibility](page-visibility.md) for the contract, rollout and frontend expectations. Vue is unchanged.
