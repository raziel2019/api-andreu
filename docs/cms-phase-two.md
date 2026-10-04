# CMS Phase 2

## Completed administrator sections

About exposes every current translated field, image, image alt/caption, repeatable benefits and working steps. Services supports list/create/edit/visibility/order/confirmed deletion and repeatable translated features. Its separate page editor handles existing heading/introduction/image/alt/CTA content. Portfolio edits the construction state and current copy/destinations only. Contact edits page copy and offers complete option management, preserving the six seeded choices. Settings adds logo, shared callout/footer translations and destinations to the existing contact/social editor. Home and authentication remain operational.

No packages, tables, migrations, model relationships, environment variables or API Resources were added or changed. Laravel session auth, the access-admin Gate, Blade, Form Requests, Eloquent, Flysystem, PHPUnit and Pint were reused. The approved seed snapshot remains unchanged, including four services, six contact options and both languages. Missing contact information is not fabricated.

## Forms and translations

Inputs visibly separate Català and Castellano; translation JSON is never shown to the administrator. Required groups require both keys and reject unknown locale keys. Optional service subtitle and Portfolio CTA label can be blank. Settings accepts existing sparse contact updates while requiring both translations when a shared-copy group is submitted. Old values survive validation errors, which appear next to fields; successful writes flash Catalan feedback.

About benefits/steps and service features use nested forms with add/remove/Amunt/Avall controls and numeric ordering. JavaScript maintains order after moves and preserves stable IDs. Empty browser collection markers normalize to empty arrays. Each collection permits up to 50 entries. Existing child IDs must be distinct and belong to the edited parent; a client cannot move another parent's child into a collection. Saving updates retained IDs, creates new items and deletes omitted children transactionally.

Service and contact-option list pages provide bulk numeric ordering. Smaller values appear first, with ID as deterministic tie breaker. Active toggles exclude inactive items from the unchanged public API. Slugs are generated on creation, bounded in length, and preserved on edits. Delete forms require an explicit confirmation; service features cascade through the existing foreign key. Contact options are scoped to the singleton Contact page.

## Validation and authorization

Every page and mutation retains web session authentication and access-admin authorization. All forms include CSRF protection; non-administrators receive 403 and guests redirect to login. Form Requests authorize writes and whitelist editable fields. Boolean values, nonnegative order (maximum 10000), translation structures, email, URLs, image types/extensions/dimensions/size and parent-scoped identifiers are validated before persistence. Social and LinkedIn URLs require HTTP/HTTPS. CTA destinations accept safe internal paths or HTTP/HTTPS URLs and reject protocol-relative paths, backslashes and unsafe schemes.

## Media and persistence

CmsMedia stores generated filenames on the public Laravel disk, not binaries in MariaDB. Uploads accept JPG/JPEG, PNG and WebP up to 5 MB and 6000 × 6000 pixels. Uploaded SVG and disguised extensions are rejected; trusted seeded SVG remains supported. About and Services page accessibility copy is editable; existing Home alt text remains editable. Service icons and logo accompany text labels rather than introducing an unrelated media library.

SaveContent stores new files, locks/persists records and synchronizes children in a transaction. Failed persistence removes newly uploaded files and retains previous content. No replacement means the original path survives. Explicit removal or replacement deletes old files only after commit and only if Home, summaries, About, Services page, services or settings no longer reference them. Service deletion uses the same reference-aware cleanup. Phase 1 UpdateHomePage reuses CmsMedia cleanup. No Nginx or Docker configuration change was needed.

## Public API contract

All six Phase 1 GET endpoints and response structures are unchanged. Shared footer/callout/logo fields already existed in `/api/v1/site`; Phase 2 makes them editable without adding API keys. Services page copy remains in services `meta.page`. Locale defaults to Catalan, supports ca/es, outputs localized strings and retains fallback/filtering/ordering behavior. Blog remains absent. Frontend API integration is separate; Vue files were not modified.

## Files created

- Controllers in `app/Http/Controllers/Admin`: AboutPageController, ServiceController, ServicePageController, ServiceOrderController, PortfolioPageController, ContactPageController, ContactServiceOptionController, ContactServiceOptionOrderController.
- Requests in `app/Http/Requests/Admin`: CmsFormRequest, ConfirmDeleteRequest, SaveServiceRequest, SaveContactServiceOptionRequest, UpdateAboutPageRequest, UpdateServicePageRequest, UpdateServiceOrderRequest, UpdatePortfolioPageRequest, UpdateContactPageRequest, UpdateContactServiceOptionOrderRequest.
- `app/Rules/CmsDestination.php`; services CmsMedia, SaveContent, SyncContentItems.
- Admin views: about, services, service-form, service-page, portfolio, contact, contact-options, contact-option-form, content-item-fields.
- Admin components: error, field, image-field, active-field, repeatable; `public/admin-repeatables.js`.
- Feature tests: AboutPageAdminTest, ServiceAdminTest, PortfolioPageAdminTest, ContactPageAdminTest, GlobalContentAdminTest, PhaseTwoSecurityTest, CmsMediaTest.
- This document.

## Files modified

- `routes/web.php`; SiteSettingController; UpdateSiteSettingRequest; UpdateHomePage service.
- Admin dashboard, home and settings views; layout and translated-field components.
- `lang/ca/admin.php`, `lang/ca/validation.php`; `tests/TestCase.php` shared CMS test helpers.
- `docs/cms-architecture.md`.

No infer-conventions output was found in the repository; existing Phase 1 conventions were followed. AGENTS.md and the Laravel/testing skills were read, and Boost documentation/schema tools were used through its installed Artisan bridge.

## Verification

Docker services used: app, db and nginx. These actual checks passed:

- `php artisan migrate:fresh --seed` against MariaDB.
- `php artisan test --compact`: **201 tests, 754 assertions**, including all 109 Phase 1 tests and 92 additional cases.
- `php artisan route:list --except-vendor`: 37 application routes, no Blog or project CRUD.
- `composer validate`: valid.
- `vendor/bin/pint --format agent`: passed across the project.
- `nginx -t`: successful.
- Live HTTP requests through Nginx: admin login and all six public API endpoints returned 200.

Tests cover administrator writes, bilingual persistence, repeatable CRUD/order/ownership, activation/filtering, confirmed deletion, shared settings, unsafe URL/email inputs, upload validation and reference preservation, transaction rollback cleanup, field-level error feedback, guest/non-admin access, CSRF and API regression. Edit flows and login/logout are exercised through HTTP feature tests; interactive browser verification was not performed. PHPUnit uses isolated SQLite; the fresh migration/seed separately verifies MariaDB. No deployment occurred.

## Remaining work

No current Phase 2 editorial section remains unimplemented. Separate Vue API wiring, production account provisioning/password recovery, and optional media transformations/maintenance can be handled later. SEO, Blog, project details and deployment remain outside this phase.
