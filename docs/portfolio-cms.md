# Portfolio CMS

Reviewed and completed 2026-10-12 for Andreu Gurrera. Laravel 13.34.0 / PHP 8.5.11 in Docker. The sibling frontend contract at `../Frontend/docs/portfolio-api-contract.md` was accessible and inspected read-only. No Vue changes or deployment were performed.

## Audit and gap analysis

| Requirement | Existing implementation reused | Completion |
| --- | --- | --- |
| Projects/categories | `PortfolioProject`, `PortfolioCategory`, relationship and existing migration | No duplicate model, table or public route |
| Create/edit/delete | Existing controllers, Form Requests, Blade forms, confirmed deletion | Retained; expanded boundary coverage |
| Publish/unpublish, stable slugs | `status` select, explicit unique slug | Retained; tested transitions without changing slug |
| Project order | Per-project `sort_order`, deterministic API scopes | Added transactional ordering form to project list, following Services |
| Cover/media reuse/uploads | `MediaAsset`, shared picker, `CmsMedia`, `MediaCatalog`, `SaveContent` | Retained; files survive unassignment/deletion |
| Gallery | JSON `gallery`, upload/reuse, repeatable row controls, bilingual alt | Added bilingual plain captions; legacy API reads sort without rewriting records |
| Public API | Existing list/detail Resources, filtering, related projects, visibility gates | Additive caption only; listing/detail key contracts and bounded query count tested |
| Draft preview | Missing | Admin session-only preview of saved CA/ES content |
| Rich descriptions/body | Universal Tiptap editor, `RichText`, `HasTranslations` | Reused; credential-bearing links removed on client/server |
| Global WhatsApp | Page-level URL only | Added settings column, normalization, derived site API URL |
| Seeds | Approved content, first-or-create behavior, no project records | No seeder execution or fake project seeds |

There is no gallery model: images are ordered entries in the existing project's JSON field. Their binaries and reusable metadata live in the universal catalog. Captions do not require a migration. `.ai/rules` is absent. Boost tools were not exposed in this session; installed framework code, Artisan and official Laravel 13 documentation were used as fallbacks.

## Screens and exact admin routes

All routes below use `web`, `auth`, `can:access-admin`; write forms include CSRF. Guests redirect to `/admin/login`, authenticated non-admins receive 403. Form Requests authorize mutations too.

| Method | Path | Name / purpose |
| --- | --- | --- |
| GET | `/admin/portfolio` | `admin.portfolio.edit`: page copy, construction state, compatibility URL |
| PUT | `/admin/portfolio` | `admin.portfolio.update` |
| GET | `/admin/portfolio/projects` | `admin.portfolio.projects.index`: projects and order form |
| GET | `/admin/portfolio/projects/create` | `admin.portfolio.projects.create` |
| POST | `/admin/portfolio/projects` | `admin.portfolio.projects.store` |
| GET | `/admin/portfolio/projects/{project}/edit` | `admin.portfolio.projects.edit` |
| PUT/PATCH | `/admin/portfolio/projects/{project}` | `admin.portfolio.projects.update` |
| DELETE | `/admin/portfolio/projects/{project}` | `admin.portfolio.projects.destroy`, requires `confirm_delete=1` |
| PUT | `/admin/portfolio/projects/order` | `admin.portfolio.projects.order`, transactional ordering |
| GET | `/admin/portfolio/projects/{project}/preview?locale=ca\|es` | `admin.portfolio.projects.preview` |
| GET | `/admin/portfolio/categories` | `admin.portfolio.categories.index` |
| GET | `/admin/portfolio/categories/create` | `admin.portfolio.categories.create` |
| POST | `/admin/portfolio/categories` | `admin.portfolio.categories.store` |
| GET | `/admin/portfolio/categories/{category}/edit` | `admin.portfolio.categories.edit` |
| PUT/PATCH | `/admin/portfolio/categories/{category}` | `admin.portfolio.categories.update` |
| DELETE | `/admin/portfolio/categories/{category}` | `admin.portfolio.categories.destroy`, confirmed; projects keep content and category becomes null |
| GET/PUT | `/admin/settings` | `admin.settings.edit/update`: existing Identity and Contact screen, WhatsApp |
| GET/POST | `/admin/media` | Existing catalog browse/upload |

Route parameters for admin resources are numeric IDs. Public project details use stable slugs. Status is exactly `draft` or `published`; publishing does not regenerate the slug. Editors can deliberately change a slug, which changes its public URL; there is no redirect-history system.

Preview renders the saved project with the same localized/sanitized Resource as the public API. It works for drafts and when Portfolio is hidden or under construction. It is an editorial preview inside the existing admin shell, not a copy of the Vue/Figma layout. It does not include unsaved editor input. Responses set `Cache-Control: private, no-store` and `X-Robots-Tag: noindex, nofollow`. No token-based public preview URL or public draft endpoint exists.

## Project and gallery save format

The existing project form submits `title[ca/es]`, optional `description[ca/es]`, `body[ca/es]`, `featured_image_alt[ca/es]`, explicit `slug`, optional `portfolio_category_id`, `year`, `client`, `status`, `sort_order` and gallery rows. Titles require both CA and ES; optional translations retain the existing CA fallback on API reads. Description/body use the existing rich editor in both locales; source mode uses the same form fields.

Cover assignment: `featured_image` upload, `featured_image_asset_id` catalog ID, or `remove_featured_image`. Existing upload validation and immutable UUID paths apply. Never submit arbitrary paths.

Each gallery form row supports:

- `image`: validated new raster upload, or `image_asset_id`: reusable public catalog ID.
- `remove_image`: unlink flag; the repeatable “Treu” control removes the whole row.
- `alt[ca/es]`: optional accessible plain text, each up to 2,000 characters.
- `caption[ca/es]`: optional displayed plain caption, each up to 2,000 characters.
- `sort_order`: integer 0–10,000; up/down controls renumber rows.

Maximum 50 images per project. Saved gallery entries are `{path, alt: {ca, es}, caption: {ca, es}, sort_order}`; API entries use URLs and localized scalar values. Empty or omitted captions do not reuse alt text. Legacy entries without caption return null. API read-time sorting does not change stored content. Removing gallery rows, replacing media or deleting projects preserves reusable files/catalog entries. Existing aggregate server upload limits still apply to batches.

Project list ordering submits `items[][id, sort_order]`, maximum 200 rows per request. Duplicate/unknown IDs and invalid order are rejected before persistence; updates run in a transaction. Public ties resolve by project ID.

## WhatsApp and migration

New migration: `2026_10_12_133107_add_whatsapp_number_to_site_settings_table.php` adds nullable `site_settings.whatsapp_number VARCHAR(16) DEFAULT '+34694229352'`. The database default initializes existing/fresh settings; other contact data, media, theme values and translations remain unchanged. Only this migration was applied to the verified local Docker `db` service. The already-applied Portfolio migration `2026_10_12_114348_create_portfolio_projects_and_categories.php` was reused unchanged.

Identity and Contact accepts international numbers with `+`, `00`, or country-code digits. Spaces, parentheses, dots and hyphens are removed; storage uses `+` followed by 8–15 digits beginning with 1–9. No country prefix is guessed for local numbers. URLs, letters/extensions, controls, arrays, repeated plus signs and invalid lengths are rejected. This validates syntax, not whether the phone is registered with WhatsApp. Omitted input preserves the saved number; a blank value clears it to null.

`GET /api/v1/site` adds `data.whatsapp_number` and derived `data.whatsapp_url`, initially `+34694229352` and `https://wa.me/34694229352`. Blank/invalid stored numbers produce a null URL. No separate editable global URL is stored. Existing phone/email, Contact form, mailto and Turnstile remain intact. The pre-existing page `whatsapp_url` remains a compatibility fallback; the current frontend already prioritizes a safe global URL. Clearing the global setting does not disable the frontend's existing fallback CTA.

Rollback drops the new number column and loses saved WhatsApp configuration; it does not drop projects/media or revert other content. Prefer forward fixes after configuration changes.

## Verification and local end-to-end workflow

Automated coverage lives in existing `PortfolioProjectTest`, `RichTextCmsTest`, visibility/media/security suites, plus `PortfolioCmsCompletionTest` and `WhatsAppSettingTest`. PHPUnit uses isolated in-memory SQLite and fake public storage, never the editorial MariaDB database.

Checks:

```sh
docker compose exec -T app php artisan test --compact
docker compose exec -T app vendor/bin/pint --dirty --format agent
docker compose exec -T app composer validate --strict
docker compose exec -T app php artisan migrate:status --no-interaction
docker compose exec -T app php artisan route:list --path=api/v1 --except-vendor --no-interaction
docker compose exec -T app php artisan route:list --path=portfolio --except-vendor --no-interaction
npm run build
node --check resources/js/rich-text-policy.js
```

1. Use the existing local Docker stack and administrator account. Apply outstanding migrations with `php artisan migrate --no-interaction` in a local environment; never use `migrate:fresh` against retained content. No reseeding is needed.
2. Open `/admin/settings`; inspect or save the authorized WhatsApp number. Inspect `/api/v1/site?locale=ca` and `?locale=es` for both additive fields.
3. In `/admin/portfolio/categories`, create approved CA/ES categories. In `/admin/portfolio/projects/create`, enter a real project, stable slug, rich CA/ES description/body, cover, gallery captions/alt and draft status.
4. Save as draft; use the CA/ES preview links. Public `/api/v1/projects/{slug}` must return 404 while draft.
5. Publish approved content, ensure Portfolio is globally visible at `/admin/pages/visibility` and disable “En construcció” at `/admin/portfolio`. These existing editorial flags are preserved by this work, not changed automatically.
6. Inspect `/api/v1/projects?locale=ca`, `?locale=es&category={slug}` and project detail. Reorder gallery/projects; check displayed order, independent captions/alt, safe HTML and related project exclusions.
7. With the existing frontend pointing to the backend origin, open `/portfolio` and `/portfolio/{slug}`; switch CA/ES, filter categories, inspect rich text/gallery/WhatsApp. The sibling contract already supports optional captions and global WhatsApp; no Vue code is required for these additions.
8. Unpublish the approved project to verify public 404 and removal from related/filter metadata. Deletion requires explicit confirmation and keeps files in the media library. Use isolated test data for any destructive manual checks.

No fake projects were inserted into the editorial database. Populated project response behavior is verified with test fixtures. Current live/local content availability depends on the preserved editor-controlled visibility/construction state and supplied approved projects.

Final local HTTP checks through Nginx confirmed 200 for `/api/v1/site?locale=ca` with the default global number/URL and `/api/v1/pages/portfolio?locale=ca` with `under_construction=false`. These checks were read-only.
