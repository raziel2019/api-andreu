# Persistent theme settings

## Laravel implementation

`SiteSetting` remains the singleton source of truth (ID 1). Migration `2026_10_11_111753_add_theme_colors_to_site_settings_table.php` adds two non-null `varchar(7)` columns:

| Column / API key | Initial database default | Validation |
| --- | --- | --- |
| `primary_color` | `#8F6C35` | Six hexadecimal digits with leading `#` |
| `background_color` | `#FFFFFF` | Six hexadecimal digits with leading `#` |

The additive migration supplies defaults to existing rows and preserves all contact, legal and editorial content. New rows receive the same database defaults. The existing `ApprovedContentSeeder::firstOrCreate` strategy preserves administrator choices on subsequent seeds; no update/reset operation was added to seeding. Migration rollback drops the two columns and therefore loses saved colors; production rollback should use a considered forward fix instead.

`GET /admin/appearance` reads saved settings. `PUT /admin/appearance` is named `admin.appearance.update`, runs in the existing session/CSRF/auth/admin-Gate group and uses `UpdateAppearanceRequest` plus the existing `SaveContent` operation. Only the two validated color fields are persisted. `/admin/settings` retains its existing validation and does not accept theme fields or aliases.

Both values are required. Standard Laravel string trimming applies; input must then match exactly `#RRGGBB`, with either letter case accepted and persisted as uppercase. Short hex, missing `#`, alpha channels, non-hex characters, arrays, numeric values and arbitrary CSS/HTML are rejected. Validation failure preserves both previously saved colors and returns the administrator to the form with field errors and previous input. JSON requests to the authenticated admin endpoint receive Laravel's normal 422 validation response. Public API mutation methods remain unavailable.

The admin uses its existing Figma shell and neutral controls; saved theme colors affect only a scoped live preview. Each field offers a hexadecimal input, synchronized native color picker and individual reset button. Reset displays a confirmation, prepares that field's default and leaves the other value intact; persistence still requires Save. The header Save button targets the same form. Without JavaScript, hexadecimal inputs and server-side save still work; selectors, reset and preview are progressively enabled by `public/admin-theme.js`. These are admin assets only.

## Exact public contract

`GET /api/v1/site`, optionally with `?locale=ca` or `?locale=es`, retains the existing `{ "data": { ... } }` wrapper and adds:

```json
{
  "data": {
    "theme": {
      "primary_color": "#8F6C35",
      "background_color": "#FFFFFF"
    }
  }
}
```

This is an excerpt: `data` also retains `id`, `pages`, `location`, `callout_title`, `callout_content`, `callout_label`, `footer_description`, `footer_cta_label`, `footer_copyright`, `contact_email`, `phone`, `linkedin_url`, `instagram_url`, `logo_url`, `callout_destination` and `footer_cta_destination`, with their previous types and locale behavior. The eight page-visibility keys and hidden-page API 404 behavior are unchanged. Theme strings are global, not translated, and have identical values in CA/ES.

For example, after saving `#123456` / `#F5F2EB`, the next GET returns those stored values in `data.theme`. SiteSettingResource reads the columns directly: it does not substitute hardcoded defaults or environment values. SiteSettingController queries ID 1 on every request; there is no content cache to invalidate or configuration cache to clear. If downstream CDN/frontend caching is introduced, that layer must refresh its own stored response.

Actual HTTP response observed through the running local Nginx/PHP 8.5/MariaDB services after the additive migration (visibility reflects the existing local content, not a reset):

```json
{
  "data": {
    "id": 1,
    "pages": {
      "home": true,
      "about": false,
      "services": true,
      "portfolio": false,
      "contact": true,
      "legal_notice": false,
      "privacy": false,
      "cookies": false
    },
    "theme": {
      "primary_color": "#8F6C35",
      "background_color": "#FFFFFF"
    },
    "location": "Catalunya · Projectes presencials i a distància",
    "callout_title": "Tens un projecte o una necessitat? Parlem-ne, sense compromís.",
    "callout_content": "Cada arxiu és un món. Explica’m el teu projecte i trobem la millor manera de cuidar-lo.",
    "callout_label": "Contacta amb mi",
    "footer_description": "Arxivística, investigació i patrimoni documental.",
    "footer_cta_label": "Parlem del teu projecte",
    "footer_copyright": "© 2026 Andreu Gurrera",
    "contact_email": null,
    "phone": null,
    "linkedin_url": null,
    "instagram_url": null,
    "logo_url": "http://localhost:8080/storage/seed/logo.svg",
    "callout_destination": "/contacto",
    "footer_cta_destination": "/contacto"
  }
}
```

## Vue integration instructions for the next phase

No Vue file or public frontend asset was modified. The currently inspected frontend explicitly parses known site fields and ignores the new object, so this additive change does not alter its existing behavior. Its static CSS tokens continue to determine public colors until integration is implemented.

In a separate authorized frontend phase:

1. Extend `src/types/cms.ts` with a theme type and a `theme` field on `SiteSettings`. During a staged rollout, accept a missing theme and retain existing defaults.
2. Extend `parseSiteSettings` in `src/services/cms.service.ts` to parse `data.theme`. Accept only strings matching `/^#[0-9A-Fa-f]{6}$/`, normalizing uppercase. Use `#8F6C35` / `#FFFFFF` only as compatibility fallback for a missing/invalid object or failed request; preserve all current parsing of pages and localized content.
3. Apply validated values when settings load in `src/layouts/SiteLayout.vue`. Map `theme.primary_color` to the existing `--color-brand` token and `theme.background_color` to `--color-surface` in `src/styles/tokens.css`. The public body currently uses `var(--color-surface)` in `src/styles/main.css`. Use a root style binding or `document.documentElement.style.setProperty`; do not concatenate arbitrary CSS or inject HTML.
4. Audit remaining hardcoded bronze values, including `src/components/NavigationMenu.vue`, and use the approved brand token where theme changes should apply. Keep distinct warm surfaces, borders, text and `--color-on-brand` tokens intact unless a separate design decision changes them. Check readable contrast for selected colors.
5. Keep theme global when switching CA/ES. Refresh settings on a new site load; retain approved fallback tokens if fetching fails. Do not invent a third language, Blog section or mutation endpoint.
6. Add frontend coverage for persisted theme, legacy/missing-theme responses, malformed values, locale changes and unchanged visibility/contact behavior. This work remains outside the Laravel task.

## Administrator provisioning correction

`AdminUserSeeder` now returns without creating or promoting accounts outside `local` and `testing`, even when environment credentials exist or `db:seed --force` is used. Development seeding keeps its existing idempotence and password-hashing behavior.

Production operators can explicitly run the new command on a trusted application console:

```sh
docker compose exec app php artisan cms:provision-admin administrator@example.com
```

Replace the illustrative email with the intended administrator. This command was tested in isolation; it was not run to create an account in the live local database. It validates and normalizes email, asks for a hidden password and hidden confirmation, requires at least 12 characters and at most 72 bytes, rejects null bytes, and hashes through the existing User cast. The byte limit avoids bcrypt truncation ([PHP password_hash documentation](https://www.php.net/manual/en/function.password-hash.php)). Passwords have no CLI option, are not read from environment admin credentials, and are not logged or echoed; a terminal without hidden-input support fails closed. Creating a new account requires interactive input; `--no-interaction` refuses creation. Repeating the command for an existing administrator succeeds without resetting credentials. Existing non-admin users are rejected without promotion or modification. A concurrent unique-email conflict does not overwrite the account.

The former production-seeding failure is now fixed without enabling automated production administrator creation. Tests initialize their isolated SQLite schema before switching the application environment to production, so standalone execution does not trigger migration confirmation in an uninitialized test database.

## Verification and scope

- Full Docker suite: **354 passed / 1571 assertions**; the previous seeder failure is resolved.
- Theme coverage, including the production-response case: **29 passed / 92 assertions**. Secure provisioning coverage, including bcrypt byte limits and null-byte rejection: **11 passed / 44 assertions**.
- Pint `--dirty --format agent`, Composer validation, `node --check public/admin-theme.js`, and diff whitespace checks pass.
- Route inspection shows 48 application routes, including the new admin-only PUT appearance route and no public theme writes.
- Additive migration ran successfully on the existing local MariaDB instance. Nginx configuration and PHP 8.5.11 checked; live API response above read successfully through Nginx.
- Chrome verified enabled pickers, manual save association, hex-to-picker/preview synchronization, visible unsaved state, loaded existing Figma icons and reset confirmation. No custom theme was saved into the live database during visual review.
- There was no deployment, Vue edit, public frontend asset edit, Blog addition, English content model or dependency change.


## Files in this Laravel follow-up

- `app/Models/SiteSetting.php`
- `app/Http/Resources/SiteSettingResource.php`
- `app/Http/Controllers/Admin/AppearanceController.php`
- `app/Http/Controllers/Admin/DashboardController.php` (appearance action moved to its focused controller)
- `app/Http/Requests/Admin/UpdateAppearanceRequest.php`
- `app/Console/Commands/ProvisionAdmin.php`
- `database/migrations/2026_10_11_111753_add_theme_colors_to_site_settings_table.php`
- `database/seeders/AdminUserSeeder.php`
- `routes/web.php`
- `resources/views/admin/appearance.blade.php`
- `public/admin-theme.css`
- `public/admin-theme.js`
- `tests/Feature/AppearanceSettingsTest.php`
- `tests/Feature/ProvisionAdminTest.php`
- `tests/Feature/ContentSeedingTest.php`
- `tests/Feature/AdminRedesignTest.php` (obsolete unsupported-theme expectation replaced with legacy settings boundary)
- `docs/theme-settings.md`
- `docs/cms-architecture.md`
- `docs/admin-figma-redesign.md`

Other pending files belong to the preceding CMS visual redesign; this follow-up preserves those changes.
