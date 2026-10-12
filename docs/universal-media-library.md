# Universal CMS media library

## Scope and audit

Audit performed on 2026-10-12 against the Laravel models, migrations, controllers, Form Requests, API Resources, Blade editors, seed assets and the sibling Vue repository (`Frontend/src/components` and `Frontend/src/views`). Vue was inspected read-only. Laravel Boost 2.10.1 database-schema and search-docs were used through its installed command interface. Installed framework: Laravel 13.34.0; PHP 8.5; Imagick 3.8.1.

One authenticated CMS and one public Storage disk remain the source of truth. The library supports every existing backend media field, and adds assignments for image/icon occurrences already present as static Vue assets. The media-library phase did not introduce projects or galleries. The existing Portfolio implementation now reuses this same catalog and picker; see [Portfolio CMS](portfolio-cms.md). No Blog or new locale is introduced.

## Exact slot inventory

### Existing CMS fields, connected now

| Area | Database field / slot key | Existing public field | Editor |
| --- | --- | --- | --- |
| Home hero | `home_pages.hero_image_path` / `home-hero` | Home `hero_image_url` | Home and library |
| Home Portfolio teaser | `home_pages.portfolio_image_path` / `home-portfolio` | Home `portfolio_image_url` | Home and library |
| Home summaries, every current record | `home_service_summaries.icon_path` / `summary-{id}` | Home summary `icon_url`, optional preset `icon` | Home and library |
| About photograph | `about_pages.image_path` / `about-image` | About `image_url` | About and library |
| Services hero | `service_pages.image_path` / `services-image` | Services `meta.page.image_url` | Services page and library |
| Services, every current record | `services.icon_path` / `service-{id}` | Service `icon_url` | Service editor and library |
| Shared branding | `site_settings.logo_path` / `site-logo` | Site `logo_url` | Settings and library |

The fixed five fields plus service/summary records were already present. There are no new columns for these assignments. The approved seed has four services and four summaries, but discovery and rendering use the actual records, including additional records and empty collections. The existing twelve-summary validation limit remains a form safety limit, not a fixed number of rendered cards.

Home still offers `archive`, `search`, `scan` and `book-open`. Custom upload/selection/removal clears `icon` to null while updating `icon_path`; presets remain optional. Text, slug, sort order and visibility retain their existing rules.

### Existing static Vue slots, new backend support, Vue integration pending

All shared keys below persist in nullable JSON `site_settings.interface_media`. Their registry key is `interface-{key}`. All can be edited in Settings or the library. JSON values are public disk paths; the public API returns URLs. Identical artwork appearing in multiple components shares one logical configurable slot.

| Key | Existing static asset | Vue consumer(s) |
| --- | --- | --- |
| `action_arrow` | `/images/0-imgIcon2.svg` | ActionLink, ContactForm submit |
| `service_arrow` | `/images/0-imgIcon4.svg` | ServiceSummaryCard, ServiceDetailCard contact link |
| `footer_arrow` | `/images/0-imgIcon9.svg` | SiteFooter, WorkingMethod |
| `contact_callout` | `/images/0-imgIcon8.svg` | ContactCallout on all pages, including Home |
| `contact_message` | `/images/phase3-contact-message.svg` | ContactIntroduction heading |
| `contact_location` | `/images/phase3-contact-location.svg` | ContactIntroduction location |
| `menu_open` | `/images/0-imgIcon1.svg` | MenuToggle closed state |
| `menu_close` | `/images/2-imgIcon1.svg` | MenuToggle expanded state |
| `email` | `/images/2-imgIcon2.svg` | SiteHeader desktop/mobile contact, SocialLinks email |
| `instagram` | `/images/2-imgSocialIcon.svg` | SocialLinks |
| `linkedin` | `/images/2-imgSocialIcon1.svg` | SocialLinks |

Each service feature already has a static `/images/phase2-2-imgIcon4.svg` icon in ServiceDetailCard. Nullable `service_features.icon_path` now permits an independent assignment per feature (`feature-{id}`). Pickers appear in the existing feature repeatable editor and the library. `features[*].icon_url` is an additive API field.

The current Vue service view maps features to strings; integrating feature media requires retaining the feature objects (or their URLs) through that mapping. Shared icons require consuming `data.interface_media` from `/api/v1/site`. Existing static assets remain unchanged, and all new assignments initially return null. Vue should preserve existing static artwork until a configured URL is supplied; the frontend must define its desired removal/fallback presentation when integrating these nullable fields.

BrandLogo has desktop `/images/0-imgIcon.svg` and mobile `/images/1-imgIcon.svg` fallbacks. These are fallback renditions of the existing logo slot, not new independent content slots; `logo_url` already overrides both. No separate mobile logo field was invented.

### Portfolio media and remaining absent slots

Portfolio projects now assign `featured_image_path` and ordered JSON gallery paths using the same catalog. Each cover/gallery image has independent CA/ES alt text; gallery captions are separate plain CA/ES fields. These assignments are managed in the project form, not as fixed global registry slots. No duplicate media table or gallery model is needed. The page action-link icon still uses the shared arrow slot above; the Home Portfolio teaser retains its existing photograph. Contact has no photograph, attachment or gallery slot; its existing two decorative icons and submit icon are covered above. About benefits and working-method steps have no independent media slots. No additional photography was invented for shared callouts or the footer. Fonts and internal admin shell icons are implementation assets, not public CMS content.

## Catalog and migrations

Additive migrations:

- `2026_10_12_083151_create_media_assets_table`: catalog metadata and unique `(disk, path)` index.
- `2026_10_12_083152_add_interface_media_to_site_settings_table`: nullable JSON assignments for audited shared icons.
- `2026_10_12_083153_add_icon_path_to_service_features_table`: nullable per-feature media path.

`MediaAsset` records contain id, disk, immutable generated path, original filename, detected MIME type, encoded byte size, width/height for raster images, optional CA/ES alternative text and timestamps. Binaries stay on disk. The chosen asset's ID is submitted by the picker; its server-resolved immutable path is written into the existing content field. This deliberately preserves the existing path-based schema/API instead of introducing duplicate assignment columns. A unique disk/path pair prevents duplicate entities when registering the same existing file, including the shared `seed/documents.png`.

Existing per-slot CA/ES alternative text remains authoritative for editorial photographs; catalog alternative text supplies the library preview. Service and interface icons remain decorative and can use empty alt text in Vue. No translations are replaced by selecting media.

Installation/upgrade (run in the target environment during a separately authorized release):

```sh
php artisan migrate --no-interaction
php artisan cms:import-media --no-interaction
php artisan storage:link --no-interaction
```

Production migration requires Laravel's explicit production flag when deployment is authorized. Only local Docker migrations were applied during this task.

`cms:import-media` registers suitable existing public files and referenced paths without copying, renaming, deleting or changing their assignments. It is restartable/idempotent. Missing, unsafe and external URL references are not imported; their existing records are preserved. Arbitrary SVG is not added to the selectable catalog. The approved content seeder invokes the same catalog importer after its existing non-destructive seed operation. Reseeding does not reset custom assignments or delete media. Rollback drops metadata/new assignment columns and would lose new assignments; files and original path columns are retained. Prefer a forward fix after new assignments have been published.

## Uploads and security

Accepted uploads: still PNG, JPEG (`.jpg`/`.jpeg`) and WebP. Limits: 5 MiB input, 6000 pixels on each axis and 16 million total pixels. AVIF is rejected. The current GD runtime supports PNG/JPEG but not WebP; existing Imagick 3.8.1 supports WebP and provides the fallback decoder/encoder. No dependency was added. Plesk must provide GD PNG/JPEG plus GD WebP or Imagick with its WEBP coder.

Authorization uses existing `auth`, `can:access-admin`, CMS Form Requests and CSRF. Validation checks file content, allowed MIME-derived format, filename extension, size and dimensions. The storage service additionally decodes the pixels and re-encodes the raster under a UUID filename. Original filenames are metadata only and are escaped in Blade. Embedded metadata and appended active payloads do not survive encoding. WebP requires a RIFF/WEBP signature, a single frame and constrained Imagick memory/map/disk resources. Unsupported or undecodable images become validation errors. New files/catalog entries are removed on a failed content transaction; existing shared files are retained.

**Uploaded SVG is rejected, including otherwise safe-looking SVG.** There is no sanitizer and no claim of safe arbitrary SVG support. Tests cover scripts, event handlers, external resources, foreignObject, JavaScript URLs, disguised SVG and a plain path-only SVG. Approved bundled SVG/presets remain supported as trusted repository assets: catalog admission requires an exact SHA-256 match against the corresponding `database/seeders/media` file. They are displayed as image URLs, never interpolated as raw markup. PHP, HTML/JS, GIF, executable extensions, malformed images and AVIF are rejected.

## Picker and save contracts

`x-admin.media-picker` is reused by the existing `image-field`, Home summaries, service-feature rows, settings and media-library assignments. It provides a native keyboard/mobile select, current/selected/uploaded preview, upload disclosure and explicitly labelled unlink checkbox. The library also offers paginated asset thumbnails and original-image preview links. Every form has unique label/input IDs. Native forms remain usable without JavaScript; live preview uses `public/admin-media.js`. Save publishes through the existing form transaction and visibility rules; there is no new draft workflow or autosave.

For a normal field `image`, the form supports `image` (new upload), `image_asset_id` (catalog selection), and `remove_image` (boolean unlink). Nested feature names use `features[index][icon]`, `[icon_asset_id]`, `[remove_icon]`. Summary custom media uses `service_summaries[index][custom_icon]`, `[custom_icon_asset_id]`, `[remove_custom_icon]` alongside optional `[icon]` presets. Shared settings fields use `interface_{key}`, `interface_{key}_asset_id`, `remove_interface_{key}`. Priority: uploaded file, explicit removal, selected existing asset, unchanged current assignment. Summary custom controls take priority over a supplied preset.

Authenticated routes:

- `GET /admin/media`: catalog browsing and server-discovered assignments.
- `POST /admin/media`: upload an unassigned asset, optional `alt[ca]`/`alt[es]`.
- `PUT /admin/media/{slot}`: upload, select or unlink the one server-owned slot.

Unknown/deleted slots return 404. Invalid/stale/missing asset IDs fail validation without changing content. Clients cannot submit an arbitrary path, model, column or disk. The existing content editors also accept catalog selections; there is no public media write API.

Unlinking an assignment, deleting a service/feature/summary or replacing a photograph does **not** delete its catalog file. Replacement never overwrites a shared file. The CMS intentionally exposes no permanent asset-delete action; physical garbage collection is separate maintenance requiring a full reference audit, backups and explicit authorization. Disk consumption therefore grows until such maintenance is performed.

## Public API compatibility

Existing endpoints and response fields remain intact. Home/About/Services/Site Resources resolve their original image/icon fields through `CmsMedia::publicUrl`. Disk paths become public disk URLs. Existing absolute HTTP/HTTPS URLs are preserved; legacy `/storage/` URLs are resolved against the configured application origin. Unsafe filesystem paths are not returned.

Additions only:

```json
{
  "data": {
    "interface_media": {
      "action_arrow": null,
      "service_arrow": null,
      "footer_arrow": null,
      "contact_callout": "https://backend.example/storage/cms/uuid.png",
      "contact_message": null,
      "contact_location": null,
      "menu_open": null,
      "menu_close": null,
      "email": null,
      "linkedin": null,
      "instagram": null
    }
  }
}
```

This object appears under `data` on `GET /api/v1/site`. `GET /api/v1/services` additionally returns nullable `data[*].features[*].icon_url`. Localized content remains CA/ES with existing fallback, sorting and visibility. API responses expose URLs, never original server filesystem roots or catalog disk metadata. Existing Vue already consumes Home/summary/service/About/Services hero/logo URLs; those assignments work with its current contracts. Shared icons and per-feature icons await Vue changes.

## Storage and persistence

The public disk root is `storage/app/public`; URLs use `APP_URL` plus `/storage`. `public/storage` resolves inside the containers to `/var/www/storage/app/public`. Current Compose mounts `./:/var/www` into **both** app and nginx, so media resides on the host filesystem and survives an app-container restart or recreation as long as that host directory is retained. Nginx's `location ^~ /storage/` serves static files, returns 404 for absent files, prevents PHP execution and adds `X-Content-Type-Options: nosniff`.

For an image-based production deployment, mount persistent media at `/var/www/storage/app/public` in the PHP container and the **same** volume at that path in Nginx (read-only there is sufficient). Do not put uploaded files only in a disposable image/container layer or a versioned release directory. Copy existing media into a new volume before first mounting it; an empty mount hides existing files. Retain the volume across releases and container replacements. Avoid `docker compose down -v` against persistent production volumes. Back up both MariaDB and media together. Ensure the PHP UID can write media and the web server can read it. Keep session/cache storage strategy separate from media durability.

For Plesk, keep `storage/app/public` in a stable shared directory outside rotating releases and link each release's `public/storage` to that directory. Point the web document root to Laravel `public`, permit the intended symlink, set `APP_URL` to the externally reachable HTTPS backend origin, rebuild cached config when it changes, and make `/storage` static with PHP/script execution disabled and `nosniff`. Align proxy/PHP upload limits with 5 MiB per file and total form size (Home or feature forms can contain multiple files). Current PHP `post_max_size` and Nginx body limit are 12 MiB; larger batches must be saved separately or these aggregate limits raised deliberately.

### Verification performed locally

On 2026-10-12, verified the public symlink, app/Nginx bind mounts, and HTTP 200 for both an existing uploaded CMS PNG and the seeded Home hero PNG. Restarted the **app** container using `docker compose restart app`, then checked the same files and HTTP responses again. SHA-256 values were identical before/after:

- Existing upload: `f13e82ca5c91c921678c89375f4f8248ebf75a9401d11584d8e6db7b7a3aa194`.
- Seed Home hero: `c456622c591120ea2795502cfa003a9e628fa7c9501380951aaa2d709ca84d9b`.

This is a real filesystem/Nginx restart check, separate from SQLite/Storage-fake tests. It verifies this local Compose mount, not a production/Plesk installation, host loss, disaster recovery or a different volume strategy. No deployment was performed.

## Validation

Feature coverage includes admin/guest/non-admin/CSRF boundaries, raster formats, malformed/disguised/oversized content, the SVG rejection matrix, raster re-encoding, every registry slot, sharing/replacement/unlinking, editor assignments, CA/ES preservation, sorting/visibility, public URLs, legacy imports and non-destructive reseeding. Existing tests that expected old-file deletion now assert retention, reflecting the requested library behavior.

Authenticated browser visual review requires a signed-in administrator; the available Chrome session was at the login screen. Server-rendered controls and save behavior are covered by feature tests. No administrator credentials were changed or provisioned for this task.

Final checks: full `php artisan test --compact` passed with **419 tests / 2109 assertions**; `vendor/bin/pint --dirty --format agent` passed; `composer validate` reported valid; `node --check public/admin-media.js` and `git diff --check` passed. Compiled Blade views were cleared after switching the picker to the project’s existing anonymous-component convention.


## Portfolio reuse — 2026-10-12

The existing project form reuses `x-admin.media-picker` for cover and every gallery row. Uploads register reusable assets under generated paths; existing IDs resolve through `MediaCatalog`, not client paths. `SavePortfolioProject` sorts rows and uses the existing transactional `SaveContent`. Gallery entries now carry independent `alt[ca/es]` and `caption[ca/es]`; the public detail localizes them and exposes `{url, alt, caption, sort_order}`. Caption defaults to null for legacy rows and is never inferred from alt. Cover/gallery removal or project deletion keeps original files/catalog entries. No data conversion is required.

Tests cover shared cover/gallery assignments, uploads, order, bilingual captions/alt, legacy fallbacks, rejected metadata and confirmed deletion retaining files. The local project create form and dynamically added caption/alt row were verified in the browser without saving sample content. See [exact API contract](portfolio-api-contract.md) and [admin workflow](portfolio-cms.md).
