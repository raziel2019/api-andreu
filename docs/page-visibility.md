# Page visibility

The five singleton models HomePage, AboutPage, ServicePage, PortfolioPage and ContactPage have a boolean `active` column, defaulting to true. LegalPage reuses its existing `active` column and retains inactive draft seeds. No generic settings table, duplicate flags, Blog or Terms are introduced. Portfolio `under_construction` is independent of publication.

## Administration

`GET /admin/pages/visibility` lists all eight pages as Visible or Oculta. Each CSRF-protected form sends `PUT /admin/pages/visibility/{page}` with a required boolean `active`. Both routes require session authentication and the existing access-admin Gate. Only visibility is saved; editorial fields, children and media stay unchanged. Missing page records return 404 and must be provisioned through existing seeds. Legal activation reuses the legal editor’s bilingual/plain-text publication validation; complete the draft in its editor before enabling it.

## Public API

Hidden or missing pages return JSON 404 with no editorial payload at `/api/v1/pages/home`, `/api/v1/pages/about`, `/api/v1/pages/portfolio`, `/api/v1/pages/contact`, and `/api/v1/services`. The Services gate blocks both the service list and `meta.page`; individual service activation remains independent. Legal endpoints retain their existing active/404 behavior. Home returns 404 without redirecting. Restoring publication immediately restores the same content. Admin editors remain available for hidden pages.

`GET /api/v1/site` retains its existing resource envelope and settings and adds:

```json
{"data":{"pages":{"home":true,"about":true,"services":true,"portfolio":true,"contact":true,"legal_notice":false,"privacy":false,"cookies":false}}}
```

These eight boolean keys are stable and locale independent. Missing records report false. `privacy` maps to LegalPage type `privacy_policy`; `cookies` maps to `cookie_policy`. The map includes no admin-only information and is read from current database state without caching.

## Frontend integration

Vue should read `data.pages` to hide navigation and footer links, and handle page API 404s, including direct visits and stale navigation state. Vue decides routing when Home is hidden. Refresh site settings after publication changes when necessary. Existing CTA destinations and shared site content are preserved; Vue should suppress links to hidden destinations where appropriate. Visibility controls page publication, not access to already public media files or removal of teasers authored on other pages. No Vue files are changed in this phase.

## Seeds and rollout

Run `docker compose exec -T app php artisan migrate --no-interaction` to add publication flags without deleting content. Existing main pages become visible. Existing firstOrCreate seed conventions preserve administrator visibility choices on repeated seed runs; legal draft defaults remain unchanged. Migration rollback removes the five new flags and loses those visibility choices, while retaining editorial content.

Coverage includes authenticated administration, role rejection, all eight hide/restore cycles, legal publication validation, 404 payloads, current site state, absent records, reseeding, child content preservation and absent Blog/Terms routes.
