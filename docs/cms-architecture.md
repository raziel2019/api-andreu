# Portfolio CMS architecture

## Architecture and package inventory

Laravel 13.34 is the editable content source of truth for the independent Vue application. MariaDB persists content; public JSON endpoints require no session. Blade renders the session-authenticated CMS. Redis and Nginx remain the existing infrastructure. Portfolio projects and categories now use the existing dedicated schema and public list/detail API. There is no Blog, registration, public content write endpoint, or contact submission/email endpoint. Contact has a dedicated server-side Turnstile verification endpoint.

Reused packages: Laravel's session guard, Gate authorization, Eloquent, Form Requests, API Resources and Flysystem; PHPUnit 12.5.37, Laravel Pao 1.1.5 and Pint 1.32.1 for checks. Boost 2.10.1 was installed by the user before implementation. The Portfolio/WhatsApp completion adds no dependencies; the existing universal rich editor uses the locked Tiptap/DOMPurify and Symfony sanitizer packages described in `rich-text-editor.md`. No Livewire, Filament or auth starter kit was previously installed or added. The Vue repository was inspected for approved content and asset references, and was not modified.

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
| `SiteSetting` / `site_settings` | Contact email, phone, normalized WhatsApp number with derived URL, translated location, social URLs, logo storage reference, shared callout/footer copy and destinations |
| `HomePage` / `home_pages` | Translated hero paragraphs, eyebrow, image description/caption, hero CTA, summaries headings, portfolio teaser, CTA copy and destinations; has many summaries |
| `HomeServiceSummary` / `home_service_summaries` | Stable slug, translated title/description, nullable whitelisted `icon` identifier, existing `icon_path` reference, order; belongs to Home |
| `AboutPage` / `about_pages` | Title, introduction, image/caption, benefits and method headings/introduction; has many benefits and steps |
| `AboutBenefit` / `about_benefits` | Translated content and order; belongs to About |
| `WorkingMethodStep` / `working_method_steps` | Translated content and order; belongs to About |
| `ServicePage` / `service_pages` | Current Services page heading, subtitle, lead, introduction, image and CTA; separate from repeatable services |
| `Service` / `services` | Stable slug, translated title, nullable subtitle, description, optional icon, active flag, order; has many features |
| `ServiceFeature` / `service_features` | Translated content and order; belongs to Service |
| `PortfolioPage` / `portfolio_pages` | Visibility/construction flags, bilingual listing/contact/related copy, rich introduction/callout/message, existing CTA/LinkedIn and compatibility WhatsApp URL |
| `PortfolioProject` / `portfolio_projects` | Stable slug, bilingual title/rich description/body, nullable category, cover and localized alt, JSON gallery with localized alt/caption/order, year/client, draft/published status and order |
| `PortfolioCategory` / `portfolio_categories` | Stable slug, bilingual title, order; has many projects; deletion leaves their category null |
| `ContactPage` / `contact_pages` | Translated title, introduction, content, conversation and service legend; has many options |
| `ContactServiceOption` / `contact_service_options` | Stable slug, translated label, active flag, order; belongs to Contact |

All new content tables have timestamps. Foreign keys cascade deletion of dependent repeatable content. Active/order and parent/order indexes support actual API queries. Relationships order by `sort_order`, then `id` for deterministic ties. There is no generic key/value CMS table.

## Translation and locale behavior

Editorial translation columns use JSON objects with `ca` and `es` string values, cast to arrays. Catalan is canonical and is fixed as the application/default/fallback locale; environment overrides cannot introduce another editorial language. Legacy plain paragraphs remain compatible. Rich editorial fields use the existing universal editor/sanitizer and expose documented HTML siblings; project detail body retains its HTML contract. See `rich-text-editor.md`.

Every public endpoint accepts `?locale=ca` or `?locale=es`, defaults to `ca` when omitted, and sends `Content-Language`. Unsupported, empty or array locale values return JSON 422. Missing or null Spanish translations fall back to Catalan. Nullable untranslated fields return null. API Resources select localized strings; they never expose the raw translation object. Admin fields show Català and Castellano separately. The admin interface uses Catalan, with the requested navigation labels.

## Admin sections

Navigation: Inicio, Sobre mi, Serveis, Portafoli, Contacte, Configuració.

- `/admin`: initial dashboard with section navigation and service count.
- `/admin/home`: complete Home editor; translated copy, destination validation, image preview/upload/removal, and add/edit/remove/reorder service summaries. Changes immediately appear in the public Home API. `PUT /admin/home` is the save action. Summary identifiers are stable lowercase slugs; maximum twelve summaries.
- `/admin/settings`: normalized international WhatsApp number, contact email, phone, CA/ES location, HTTP/HTTPS social URLs, logo upload/removal and translated shared callout/footer copy with destinations. Empty contact values remain allowed. `PUT /admin/settings` saves changes.
- `/admin/about`: full copy/image editor and add/edit/remove/reorder benefits and working-method steps.
- `/admin/services`: service list and bulk ordering; create/edit/delete services, visibility and repeatable features. `/admin/services/page` edits the Services page introduction, image/accessibility copy and CTA.
- `/admin/portfolio`: bilingual listing/contact/related copy, compatibility links and construction state. `/admin/portfolio/projects` and `/admin/portfolio/categories` manage existing project/category CRUD; project list supports transactional bulk ordering, project form reuses universal media/rich editors, and session-protected CA/ES previews render saved drafts. See [Portfolio CMS](portfolio-cms.md) for exact routes and save fields.
- `/admin/contact`: complete page copy plus contact-option list/order controls; `/admin/contact/options/create` and per-option edit screens manage bilingual labels, visibility and confirmed deletion.

Phase 2 reuses all Phase 1 models, relationships, migrations, approved seeds, auth and API Resources. No packages or environment variables were added. See [Phase 2 implementation](cms-phase-two.md) for forms, file inventory and verification.

Form Requests authorize writes and validate translated structures, required CA/ES values, lengths, emails, URLs, booleans, order and uploads. Controllers coordinate requests and responses. `UpdateHomePage` handles filesystem changes and transactional persistence; it preserves shared media references and removes new files on a failed database transaction. Only validated, intended fields are persisted. Blade escapes editorial text.

## Media

MariaDB stores paths only. Assets live on Laravel's existing `public` filesystem disk under `storage/app/public`, served through `public/storage`. Run `php artisan storage:link` after setup. Resources convert references to URLs using that disk; configure `APP_URL` for the backend origin.

Approved current assets are bundled under `database/seeders/media` and copied to `seed/` by the content seeder when missing. They include the existing logo, Home/About/document images, three summary icons and four service icons. Trusted source SVG icons are seed assets only; uploaded SVG is rejected.

Home, About, Services page, service icon and logo uploads accept JPG/JPEG, PNG and WebP, checking image contents, MIME-derived type, filename extension, 5 MB size and maximum 6000 × 6000 dimensions. Laravel generates storage filenames. Replacing/removing an assignment now retains its file in the universal reusable media catalog. Nginx serves `/storage/` as static files, preventing PHP execution, and sets `nosniff`. Docker PHP and Nginx upload limits allow the two-image form; rebuilding `app` applies the PHP configuration change. Existing images stay unchanged after invalid submissions.

`CmsMedia` centralizes validated raster decoding/re-encoding, generated-file storage and reusable assignment handling. `SaveContent` wraps persistence and child synchronization in a transaction, cleaning up newly uploaded files on failure and checking old references after commit. Existing Home persistence reuses the same cleanup service. A slot-based media library now reuses these services; transformations and unused-file maintenance remain optional future work.

## Public API and frontend contract

Content endpoints are GET/HEAD only; content writes return 405. `POST /api/v1/contact/verify` validates a Turnstile token without storing a submission or sending email. API routes use Laravel's stateless API middleware, not admin sessions, and errors render JSON.

| Endpoint | Response |
| --- | --- |
| `/api/v1/site` | `{ "data": { ... } }`: localized settings/shared content and nullable contact/social/media fields |
| `/api/v1/pages/home` | `{ "data": { ... } }`: localized Home copy, URLs/destinations, ordered `service_summaries` |
| `/api/v1/pages/about` | `{ "data": { ... } }`: localized copy, ordered `benefits` and `working_method_steps` |
| `/api/v1/pages/portfolio` | `{ "data": { ... } }`: bilingual Portfolio page copy, HTML siblings, compatibility links and construction state |
| `/api/v1/projects` | Published paginated/filterable projects, page/category metadata; order by sort order then ID |
| `/api/v1/projects/{slug}` | Published project detail, sanitized HTML body, localized gallery alt/caption, up to three related projects |
| `POST /api/v1/contact/verify` | `{ "success": true }` only after server-side Cloudflare verification; safe failures and five attempts/minute/IP |
| `/api/v1/pages/contact` | `{ "data": { ... } }`: localized copy and active, ordered `service_options` |
| `/api/v1/services` | `{ "data": [ ... ], "meta": { "locale": "ca", "page": { ... } } }`: active ordered services with ordered `features`; localized Services page editorial copy in `meta.page` |

Fields use snake_case. Every record includes `id`; repeatable items include `sort_order`, and services/options/summaries include their stable `slug`. Translated fields resolve to localized strings/null; documented HTML siblings and project body contain sanitized HTML. Media path fields are exposed as `*_url`, not database/storage paths. For example `hero_image_path` becomes `hero_image_url`, and `icon_path` becomes `icon_url`.

Home includes `hero_title`, `hero_content`, `hero_eyebrow`, `hero_image_url`, `hero_image_alt`, `hero_image_caption`, `hero_cta_label`, `hero_cta_destination`, `services_heading`, `services_introduction`, `service_summaries`, `stories_heading`, `stories_introduction`, `portfolio_title`, `portfolio_image_url`, `portfolio_image_alt`, `portfolio_cta_label`, `portfolio_cta_destination`, `cta_title`, `cta_content`, `cta_label`, `cta_destination`. Summaries have `id`, `title`, `description`, `icon`, `icon_url`, `sort_order` and `slug`.

### Editable Home summary icons

Each summary card at `/admin/home` contains a current preview and icon select alongside CA/ES title/description and existing order controls. The same CSRF-protected `PUT /admin/home` saves `service_summaries[index][icon]`. Supported identifiers are exactly `archive`, `search`, `scan`, `book-open`; identifiers never translate. Unknown values, SVG/HTML/JavaScript strings and client-supplied `icon_path` fields are rejected before any Home content changes. Omitted icons preserve the stored selection for older clients; a blank/null selection preserves the existing image path without a predefined identifier. New form cards default to `archive`.

Selections reuse trusted, repository-owned approved SVG assets on the existing `public` disk. They update `icon_path`, so consumers that already display `icon_url` can reflect icon changes without changing Vue source. Previews use the same bundled artwork as image data URLs, remaining available even when unused public files have been cleaned up. Missing selected public assets are copied from the bundle during save. The existing media library still accepts validated raster replacements; replacing a summary image clears its identifier to null and exposes the uploaded image through `icon_url`. No administrator-supplied SVG or HTML is stored or rendered.

Both `GET /api/v1/pages/home?locale=ca` and `?locale=es` return the existing `{ "data": { ... } }` wrapper. The exact summary item shape is:

```json
{
  "id": 1,
  "title": "Organització i quadres de classificació d'arxius",
  "description": "Cada document, al seu lloc.",
  "icon": "archive",
  "icon_url": "https://backend.example/storage/seed/summary-organization.svg",
  "sort_order": 0,
  "slug": "organization"
}
```

The item is located at `data.service_summaries[index]`; remaining Home fields are unchanged. `id` and `sort_order` are integers, `slug` is a string, localized `title`/`description` are strings or null under existing fallback rules, `icon` is one supported string or null for legacy/custom images, and `icon_url` is the public disk URL or null when there is no image. The backend origin follows filesystem configuration. Spanish changes only translated content; identifiers, URLs, IDs, slugs and order remain the same. Render `icon_url` as an image, or map supported identifiers to matching frontend icons; never render API content as raw HTML/SVG. Summaries remain sorted by `sort_order`, then `id`. Home visibility continues to use existing page activation behavior.

Run `php artisan migrate --no-interaction` to apply `2026_10_12_080514_add_icon_to_home_service_summaries_table.php`. It adds nullable `icon` and backfills only recognized approved asset paths, preserving custom paths, summaries, translations, order and visibility. Rollback drops the identifier but retains content and image paths. No reseeding is required for existing installations. Fresh seeding supplies organization → archive, digitization → scan, research → search, advice → book-open; advice reuses the approved bilingual Services content. Existing Home collections are never expanded or reset on reseeding, including administrator-deleted summaries and changed icons.

About children expose localized `content`. Services expose `title`, nullable `subtitle`, `description`, `icon_url`, `active`, `features`. Contact options expose `slug`, `label`, `active`. Portfolio's `linkedin_destination` is null until confirmed; `cta_destination` supports the current internal Home link.

Vue should fetch the backend origin plus these paths, pass its selected locale, read `data`, and handle null contact/social values without inventing links. Split paragraph strings on `\n\n` to preserve the current paragraph layout. Render plain fields as escaped text; use only documented sanitized HTML fields for rich rendering. Internal CTA destinations match existing Vue routes (`/sobre-mi`, `/servicios`, `/portfolio`, `/contacto`). IDs/slugs remain unchanged when switching locale. Stable contact-option slugs match current Vue form choices. UI-only form validation/button labels may remain in Vue's localization resources; editorial copy comes from the API. This phase does not modify or wire up Vue.

CORS applies only to `/api/v1/*`, allows GET/HEAD/POST/OPTIONS, exposes `Content-Language`, and uses `FRONTEND_URL` as the allowed origin. No credentials are needed or allowed. The default example is the inspected local Vue origin, `http://localhost:5173`; production domains are not hardcoded.

## Seed strategy

`ApprovedContentSeeder` reads the repository-owned snapshot `database/seeders/content/approved-content.json`, copied from the current Vue `src/locales/ca.ts` and `es.ts`. The Catalan About/Services text matches `Frontend/docs/client-revision-01.md`; Spanish is the current complete translation. No obsolete design copy is used. Seed image mappings follow current Vue components, not old Figma manifests.

Fresh seeding provides Home (including four summaries), About (three benefits and three steps), four Services (twelve features plus Services page editorial content), construction-only Portfolio, Contact (six approved options), and site settings. Contact location is the existing approved location. Email, phone, LinkedIn and Instagram remain null. Reseeding preserves existing page/editor changes and existing service values, and does not reset an existing administrator password. Child collections seed when their parent is first created; the seeder is not a content synchronization command.

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

All current editorial CMS sections are editable after Phase 2. Optional future work includes media transformations, production administrator provisioning or password recovery, and separate Vue API integration. Portfolio project details, captions, previews and global WhatsApp are now complete; SEO beyond existing fields remains outside this task. Blog remains excluded. Nothing was deployed and the Vue repository was not modified.

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


## CMS Figma visual redesign

Figma file `2cmVwrdFK1lFFFOQhF2pdr`, nodes `12:2`, `12:363`, `12:724` and `12:969`, now define the shared admin visual system. Existing `components/admin/layout` composes a persistent 288 px desktop sidebar, 85 px header, bronze/warm tokens and self-hosted Fraunces/Poppins. New sidebar, topbar, heading, status, statistics, card, save, page-list and action components reuse existing translated fields and editor functionality. Public CSS/JS assets keep admin rendering independent of Vite; no UI library is added. Mobile navigation uses an accessible drawer, and forms/cards stack at small widths.

Five primary navigation entries are Vista general, Contenidos y traducciones, Biblioteca de imágenes, Apariencia and Identidad y contacto. `/admin/content` links to all original page/legal editors and visibility controls. Dashboard counts the actual page map, visible pages and two supported languages, with a database service count. It embeds the configured public frontend as a read-only preview. All public-site links use the existing FRONTEND_URL through `app.frontend_url`.

`/admin/media` lists Home hero/portfolio, About, Services page, logo and current service/summary icon slots. `PUT /admin/media/{slot}` uses CMS Form Request validation and server-owned model/column mappings, then delegates to SaveContent/CmsMedia for transactional replacement and shared-reference-safe cleanup. Storage remains the existing public disk, generated cms paths and `/storage/` URLs. The universal media follow-up adds a unique disk/path MediaAsset catalog while preserving existing content path columns; no arbitrary URL editor or duplicate content state is introduced.

`/admin/appearance` now edits persisted `SiteSetting.primary_color` and `background_color` through an admin-only PUT action, strict six-digit hex validation, uppercase normalization and existing SaveContent. An additive migration supplies #8F6C35/#FFFFFF defaults to existing rows. Color pickers, hex inputs, scoped live preview and individually confirmed resets share the actual save form. SiteSettingResource adds `data.theme` from saved columns; the API query is uncached, so the next request observes saved values. Vue retains static tokens until its separate integration phase. Identity/contact remains the existing `/admin/settings` form, including original contact/legal fields and translated callout/footer copy. The global header save targets the screen's actual primary form, or is disabled when no single form exists; media saves per slot.

CA/ES remain the only editorial languages, with Catalan canonical. Blog, English, browser-only/local persistence and fake backup actions are omitted. Export/import/restore remain clearly unavailable. Authentication, CSRF, admin authorization, validation, legal publication, page visibility and API 404 contracts are retained. Vue is unchanged.

See [admin-figma-redesign.md](admin-figma-redesign.md) for node mapping, component/route inventory, storage behavior, intentional adaptations and visual/test results. Current Docker suite: 354 passed / 1571 assertions, including theme and secure provisioning coverage; the former production-admin-seeder failure is resolved. Pint, Composer validation and route inspection passed.


## Persistent theme and explicit administrator provisioning

Migration `2026_10_11_111753_add_theme_colors_to_site_settings_table.php` adds non-null seven-character `primary_color` and `background_color`, defaulting to #8F6C35 and #FFFFFF without resetting existing content. Reseeding continues using firstOrCreate and preserves theme choices. `GET /api/v1/site` adds `data.theme = { primary_color, background_color }` while retaining every previous field and the eight-key page map. Colors are global across CA/ES; no locale or content model was added.

AppearanceController and UpdateAppearanceRequest replace the former reference-only screen. Both values are required and validated as six-digit #RRGGBB, then saved uppercase; malformed input preserves old values. Only validated colors are accepted, with auth/admin Gate, session and CSRF unchanged. The live preview uses only validated colors in scoped CSS properties, and reset only prepares a value until Save is submitted. `public/admin-theme.css` and `.js` are admin-only assets.

AdminUserSeeder is restricted to local/testing. Production `db:seed`, including --force and configured development credentials, cannot create/promote administrators. `cms:provision-admin {email}` supplies an explicit trusted-console path with hidden password and confirmation, existing hashed cast, idempotence for existing administrators, refusal to alter non-admin accounts and refusal to create accounts non-interactively. The previous production-seeder test also initializes its lazy isolated test schema before switching environment. No real administrator was provisioned during verification.

See [theme-settings.md](theme-settings.md) for the exact public payload, migration behavior, security boundary, secure operator command, actual local HTTP response and future Vue token integration instructions. No Vue or public frontend assets were modified, and nothing was deployed.


## Universal media management

See [Universal CMS media library](universal-media-library.md) for the exact audited slot inventory, migrations, picker/save contracts, SVG limitation, API additions, Docker/Plesk storage strategy and actual restart verification. `/admin/media` now browses paginated assets, uploads unassigned files and assigns existing assets; `POST /admin/media` remains admin-only. All existing image-field components delegate to one media picker. Home summaries retain their four presets and also accept custom media. Service features have an additive nullable icon path; Settings has eleven audited shared interface-icon assignments. New public fields are `data.interface_media` on `/api/v1/site` and `features[*].icon_url` on Services. Those static frontend slots await Vue integration; existing media URL consumers remain compatible.

Media assets store metadata only. Selection submits an asset ID and resolves its immutable public path on the server, preserving the existing path-based assignment schema. Unlinking/replacing/deleting content retains library files. Only new uploads from a failed save are discarded. PNG/JPEG/WebP uploads are decoded and re-encoded; arbitrary SVG and AVIF are rejected. Repository-trusted seed SVG assets remain usable. Catalog import and seeding are idempotent and preserve custom assignments. Local additive migrations and an actual app-container restart were verified; nothing was deployed and Vue was not modified.


## Portfolio and WhatsApp completion — 2026-10-12

The existing project/category models, migration, API endpoints, reusable media catalog and universal rich editor were audited and reused. No duplicate domain models/routes, dependencies or fake project seeders were introduced. Additions: bilingual plain gallery captions, sorted legacy gallery reads, transactional project-list ordering, authenticated saved-content previews, credential-free rich links and CMS-managed global WhatsApp. The exact public shapes are in [Portfolio API contract](portfolio-api-contract.md); audit, admin routes and local workflow are in [Portfolio CMS](portfolio-cms.md).

New migration `2026_10_12_133107_add_whatsapp_number_to_site_settings_table.php` adds a nullable canonical number defaulting to `+34694229352`. Site API derives `https://wa.me/34694229352` from that number; existing contact, theme and translation values are preserved. Only the verified local Docker MariaDB schema was migrated. Existing Portfolio construction/visibility settings were not changed. No Vue modification or deployment occurred.

Verification: full PHP 8.5 suite passed 518 tests / 2,540 assertions; Pint, strict Composer validation, production asset build, JavaScript syntax, migration status/SQL rehearsal and API/admin route inspection passed. The optional Vite font fallback warning remains. PHPUnit uses isolated SQLite/storage fakes. Local MariaDB exercised the additive migration. `db:table` inspection encountered the existing missing `intl` extension; no dependency/runtime change was made to address that unrelated command limitation.
