# CMS Figma redesign

## Scope and references

The design-to-code workflow read high-fidelity Figma context and screenshots for file `2cmVwrdFK1lFFFOQhF2pdr` before implementation. React/Tailwind reference markup was translated into the existing Laravel Blade components and plain CSS admin styling convention. Laravel 13.34.0 and PHP 8.5 run in the repository's Docker app service. The original visual phase changed no dependencies, models, migrations, public API contracts or Vue files. The authorized theme follow-up adds one SiteSetting migration and an additive public theme object while retaining the same CMS architecture and leaving Vue untouched.

| Figma node | Implementation |
| --- | --- |
| `12:2` | Overview composition and shared shell |
| `12:363` | Overview, dynamic statistics, page list and public-site preview |
| `12:724` | Media library with actual model slots and Laravel uploads |
| `12:969` | Appearance editor, persistent colors and scoped live preview |

The supplied overview nodes depict the same screen. Content and identity reuse their visual language and the existing CMS editors rather than introduce a separate content architecture.

## Shared layout and assets

`resources/views/components/admin/layout.blade.php` remains the shared entry point. New components in that directory are `sidebar`, `topbar`, `page-heading`, `status-banner`, `stat-card`, `card`, `save-button`, `page-list`, `content-actions` and `icon`. Existing translated-field, field, image-field, repeatable, error and active-field components continue to render the original editing controls.

`public/admin.css` centralizes bronze `#8F6C35`, app background `#F5F2EB`, text `#454641`, secondary text `#686861`, borders `#E6E2D9` and white surfaces. Desktop sidebar is 288 px, header 85 px, content padding 40 px, card radius 12 px. Fraunces and Poppins are self-hosted in `public/fonts` with their OFL licenses. There were no project font files to reuse. Figma SVG icons are downloaded to `public/admin-icons`, with intrinsic root dimensions preserved. Image-library previews use existing database media, not mockup screenshots or temporary Figma URLs.

`public/admin-shell.js` implements the mobile drawer, Escape dismissal, focus trapping/return, inert closed navigation and manual unsaved-change feedback. Below 1000 px the sidebar becomes a drawer; below 640 px cards and bilingual columns stack. Existing tables have horizontal scroll wrappers when needed. Labels, escaped Blade values, aria-current navigation, skip link and visible focus outlines are retained or added.

## Routes

All new routes live inside the existing `auth` and `can:access-admin` group:

| Method | URI | Name / purpose |
| --- | --- | --- |
| GET | `/admin/content` | `admin.content.index`, entry point to existing editors |
| GET | `/admin/media` | `admin.media.index`, asset catalog and assignments |
| POST | `/admin/media` | `admin.media.store`, upload an unassigned asset |
| PUT | `/admin/media/{slot}` | `admin.media.update`, upload/select/unlink one validated slot |
| GET | `/admin/appearance` | `admin.appearance.index`, saved theme editor |
| PUT | `/admin/appearance` | `admin.appearance.update`, validated theme save |

`/admin` and `/admin/settings` retain their URLs and save contracts. Existing page, service, option, legal and visibility routes remain in place. There is no new identity model or alternate settings route.

The top save button targets `cms-editor` on page/settings/service/option editors and the populated Services order form, preserving native validation, multipart handling, CSRF and PUT spoofing. Screens without a single editable form disable global save. Media uploads have separate per-slot save buttons. No autosave or local-browser persistence is claimed or implemented.

## Dashboard, content and visibility

Dashboard uses the existing `PageVisibility::publicMap()`, counts its eight actual CMS page keys, counts visible entries and displays the two supported editorial languages. Service count comes from `Service::count()`. There are no fictitious language or customized-text statistics. The page list links to Home, About, Services page, Portfolio, Contact, Legal Notice, Privacy and Cookies editors and reflects visibility from the database.

`/admin/content` uses the same list, links to existing service management and shared settings, and exposes the existing visibility screen. CA/ES fields and all original request validation/save operations remain unchanged. Legal pages still require complete bilingual content to publish; hidden pages still return API 404s.

The preview embeds the configured frontend URL in a sandboxed frame and supplies an external link when framing or frontend loading fails. It previews the public site's own current state, not a simulated local draft. There is no preview-page selector or fabricated website preview. `app.frontend_url` reads the existing `FRONTEND_URL` environment value, and all public-site links use it. Missing configuration displays an explicit pending-configuration message.

## Media library and storage

The universal media follow-up extends the same screen into a paginated asset catalog plus assignment forms. `CmsMediaSlots` discovers the existing five fixed image fields, dynamic service/summary icons, dynamic feature icons and eleven shared interface slots identified in Vue. There is no assumed summary count. The original shell, media cards, responsive layout and existing editors are retained.

`x-admin.media-picker` replaces upload-only image-field controls and is reused in Home summaries, service features, Settings and the library. Native labelled selects, previews, upload disclosure, unique input IDs and explicitly labelled unlink checkboxes work inside the original multipart forms. `public/admin-media.js` provides preview and preset coordination. Uploading an unassigned file uses admin-only `POST /admin/media`; existing `PUT /admin/media/{slot}` now accepts existing-asset selection and unlinking as well as uploads.

A metadata-only `MediaAsset` catalog with unique disk/path identity preserves the existing content paths and public URL fields. All uploaded rasters are decoded and re-encoded. Removing/replacing an assignment retains the file for reuse and never overwrites another page's shared asset. Uploaded SVG/AVIF are rejected; existing trusted SVG artwork/presets are preserved. Additive feature/shared-icon API fields await Vue integration. See [Universal CMS media library](universal-media-library.md) for the full slot inventory, new migrations, request contracts and security/storage details.

Local filesystem/Nginx verification confirmed HTTP 200 and identical uploaded/seed image hashes after restarting the app container. The existing host bind mount is persistent; production Docker/Plesk requirements and verification limits are documented in the linked guide. Current authenticated visual review was unavailable because Chrome was at the login screen; the earlier redesign screenshots below describe the earlier phase, not a new authenticated visual test of the picker.

## Appearance and identity

The theme follow-up enables primary/background controls on the existing Figma screen. SiteSetting now stores `primary_color` and `background_color`; migration `2026_10_11_111753_add_theme_colors_to_site_settings_table.php` supplies #8F6C35/#FFFFFF defaults while preserving existing records. AppearanceController/UpdateAppearanceRequest use the existing auth/admin group, CSRF and SaveContent. Inputs accept only six-digit hex and save uppercase. Individual resets require confirmation and prepare defaults until manual save. Scoped live preview and picker/hex synchronization live in admin-only `public/admin-theme.css`/`.js`; shared components/fonts/icons are reused.

SiteSettingResource now publishes saved values in `data.theme` without hardcoded overrides. Existing site/contact fields, locale behavior and data.pages are unchanged. The API queries the database on each request, so saves need no cache clearing. Vue still uses static tokens and has not been changed; the appearance copy explains the pending public integration. See [theme-settings.md](theme-settings.md) for exact fields and integration instructions.

Identidad y contacto uses the existing `/admin/settings` form and SiteSettingController: contact email, phone, social URLs, CA/ES location, nullable legal identity fields, logo, shared callout and footer. Unknown personal/business facts remain blank; none were invented. Existing translated shared content still saves through the original endpoint.

## Intentional adaptations

- Only CA/ES appear; Catalan remains canonical. EN, three-language copy and a third content model are omitted.
- Blog navigation, editor, image slot, API and data are absent. The removed Blog card is replaced by the actual Home portfolio teaser slot.
- Real server persistence replaces Figma's local-mode notice.
- Export, import and restore controls are visibly disabled and labeled unavailable; no fake backup/local-draft actions are attached.
- Theme colors are editable and published by Laravel; applying them to the public Vue site remains a separate frontend integration task.
- The preview uses the configured public site directly rather than an unwired page selector.
- Legal pages and real visibility status extend the mockup page list.

## Verification

Executed in the repository-standard Docker app service:

- Current `php artisan test --compact`: **354 passed / 1571 assertions**, including theme saves/defaults/validation, API compatibility and explicit production provisioning. The former production-admin-seeder failure is fixed by restricting automatic seeding to local/testing; production operators use the explicit password-hidden console command.
- Theme-only coverage, including persisted values in production mode: **29 passed / 92 assertions**. Secure provisioning coverage: **11 passed / 44 assertions**.
- Existing public API, legal, page visibility, bilingual editors, shared settings and upload regressions pass in the final full run.
- `vendor/bin/pint --dirty --format agent`: passed.
- `composer validate`: valid.
- `php artisan route:list --except-vendor`: 48 routes, including authenticated PUT /admin/appearance, no Blog route and unchanged public API URLs.
- `node --check public/admin-shell.js`, `node --check public/admin-theme.js` and `git diff --check`: passed.

Feature tests use isolated in-memory SQLite and fake public storage; they do not overwrite the running CMS content. Added cases cover auth/admin boundaries, active navigation, links, counts, global-save association, persisted appearance validation and API compatibility, real slot discovery, escaping, upload/replacement, type/extension/size/dimension rejection, shared-file retention, failed upload retention, storage URLs and CSRF.

Chrome visual review covered Overview, Content, Media Library, Appearance and Identity against the Figma screenshots. Measured sidebar: 288 px; main padding: 40 px; Fraunces headings and Poppins body; every observed shell icon loaded at its intrinsic dimensions. Header's initial 87 px measurement was corrected and remeasured as 85 px by removing the save button's transparent border. All twelve current media previews loaded, with photo previews measured at 558 × 313.875 px and icons preserving their own proportions. Media previews load the actual stored images. At a 390 px viewport, body/main/scroll width all measured 390 px; drawer opening, focus placement, Escape closing and focus return were verified. Temporary viewport override was reset.

The embedded public Vue site displayed an existing content-loading error during review. This phase did not alter Vue or its API integration. No deployment, content reset or live account provisioning was performed. Automatic seeding is now restricted to local/testing and explicit production provisioning is available through the secure console command. Theme migration was applied only to the existing local Docker database.


The theme follow-up reread node 12:969 through Figma design context and reused the shared visual system. Chrome review verified editable hex/native picker synchronization, scoped preview, unsaved indicator, reset confirmation and loaded Figma icons. The new theme fields and preview intentionally extend the reference to support real Laravel persistence. No custom color choice was saved in the live local database.

Universal media follow-up final verification: 419 Laravel tests / 2109 assertions passed, Pint passed, Composer configuration valid, media JavaScript syntax and diff whitespace checks passed. Existing seed and uploaded files returned HTTP 200 with unchanged hashes after the local app restart. No Vue changes or deployment.
