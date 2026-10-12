# CMS rich text

## Scope and audit

All fields below have independent `ca` and `es` values. Catalan remains canonical; existing API locale fallback is unchanged. Editorial translations are JSON columns (`longtext` with JSON constraints in MariaDB), cast to arrays by Eloquent. The audit inspected all admin forms, requests, models, migrations, API resources, approved seed content and the local MariaDB schema/data. Existing Home/About content includes CRLF and blank-line paragraphs; services contain plain translated strings; legal bodies are currently null; the local projects table has no records. The aggregate read-only audit covered all 24 eligible fields: every populated translation column contained valid JSON and no current CA/ES value contained HTML tags. Existing safe or unsafe HTML is supported defensively without rewriting historical rows.

The shared component is `resources/views/components/admin/rich-text.blade.php`, invoked explicitly by `x-admin.translated-field` with `:rich="true"`. There are 24 model/field pairs (48 locale editors before repeating collections):

| CMS editor / model | Rich fields | API location and HTML fields |
| --- | --- | --- |
| Home / HomePage | `hero_content`, `services_introduction`, `stories_introduction`, `cta_content` | `/api/v1/pages/home`: `data.hero_content_html`, `data.services_introduction_html`, `data.stories_introduction_html`, `data.cta_content_html` |
| Home summaries / HomeServiceSummary | `description` | Home: `data.service_summaries[].description_html` |
| About / AboutPage | `introduction`, `working_method_introduction` | `/api/v1/pages/about`: `data.introduction_html`, `data.working_method_introduction_html` |
| About benefits / AboutBenefit | `content` | About: `data.benefits[].content_html` |
| About method steps / WorkingMethodStep | `content` | About: `data.working_method_steps[].content_html` |
| Services page / ServicePage | `lead`, `description` | `/api/v1/services`: `meta.page.lead_html`, `meta.page.description_html` |
| Service / Service | `description` | Services: `data[].description_html` |
| Service features / ServiceFeature | `content` | Services: `data[].features[].content_html` |
| Portfolio listing / PortfolioPage | `introduction`, `callout_content`, `message` | `/api/v1/pages/portfolio`: `data.introduction_html`, `data.callout_content_html`, `data.message_html` |
| Portfolio projects / PortfolioProject | `description`, `body` | `/api/v1/projects` and `/api/v1/projects/{slug}`: `description_html` on each project; detail `data.body` remains sanitized HTML |
| Contact / ContactPage | `introduction`, `content`, `conversation` | `/api/v1/pages/contact`: `data.introduction_html`, `data.content_html`, `data.conversation_html` |
| Legal Notice, Privacy, Cookies / LegalPage | `body` | `/api/v1/legal/legal-notice`, `/api/v1/legal/privacy`, `/api/v1/legal/cookies`: `data.body_html` |
| Global editorial settings / SiteSetting | `callout_content`, `footer_description` | `/api/v1/site`: `data.callout_content_html`, `data.footer_description_html` |

Home services/portfolio introductions and the services lead were single-line inputs but are editorial prose, so they also receive the editor. Titles, headings used as labels, subtitles, eyebrows, image captions, CTA labels, copyright, contact option labels and location remain plain short strings. Slugs, sort orders, URLs, destinations, email, phone, colors, icons, media selection, legal identity and image alt text remain technical/plain fields. The multiline gallery alt textarea intentionally remains plain text. No separate public Blade legal renderer exists; legal display is delivered through the public API to Vue.

The registry `App\Services\RichText::FIELDS` defines model sanitation scope. Update the registry, explicit Blade opt-in and corresponding Resource when adding a new rich field.

## Libraries and build

Installed/locked versions:

- Tiptap core, ProseMirror bundle and StarterKit **3.31.4** (MIT).
- DOMPurify **3.4.16** for browser HTML and paste sanitization.
- Symfony HtmlSanitizer **8.1.8** for server sanitation (PHP 8.4+, compatible with project PHP 8.5).

The admin already uses vanilla JavaScript and static CSS, with Vite available. Tiptap's vanilla API fits Blade without introducing Vue into the backend. It supplies maintained editing, selection, history and keyboard behavior, and a constrained schema. TinyMCE was considered, but Tiptap allows a small explicit schema and a locally bundled interface without a hosted editor/API key or TinyMCE's GPL/commercial licensing choice. No runtime CDN is used. StarterKit supplies bold, italic, underline, links, headings, paragraphs, lists, blockquotes and history; unsupported code, strike, horizontal rules and automatic trailing nodes are disabled.

`resources/js/admin-rich-text.js` and its CSS are a dedicated Vite entry loaded once through the component. The static admin shell remains in use. New repeatable rows are initialized automatically; removed editors are destroyed and reordered editors retain their state. Without JavaScript the existing textarea remains usable, with server sanitation still enforced.

## Visual and HTML modes

The toolbar executes real Tiptap commands: bold, italic, underline, paragraph, H2/H3, unordered/ordered lists, blockquote, create/edit/remove link, undo/redo and clear formatting. HTML switches to the named textarea used by the original form. Visual switches back after DOMPurify sanitation and Tiptap schema normalization. Direct form submission in HTML mode also applies and normalizes the source. Formatting and text survive round trips; serialization may add paragraph nodes inside list items and normalize entities. Arbitrary source markup is never mounted directly into the document.

HTML mode disables visual-only formatting commands; the textarea supports native text undo/redo. Changes applied from HTML participate in the visual document history. Plain-text paste is handled by Tiptap; HTML paste is sanitized before insertion. Normalization/paste feedback appears in an accessible status message. Invalid link URLs show an error in a modal dialog rather than being applied. The native dialog traps focus, focuses the URL, supports Enter/Escape, and returns focus when closed. Toolbar controls have labels and state; mobile controls wrap with 44px targets. Visual editors expose textbox/multiline semantics and validation errors. Shortcuts: Ctrl/Command B, I, U, Z; Shift Ctrl/Command Z for redo; Ctrl/Command K opens links.

All changes submit through the original POST/PUT forms with existing routes, CSRF, Form Requests, authentication and authorization. Locale inputs retain their original names. Laravel old input initializes each locale after validation failure. No separate save endpoint or workflow is introduced.

## Supported HTML and security

Output elements: `p`, `br`, `strong`, `em`, `u`, `h2`, `h3`, `ul`, `ol`, `li`, `blockquote`, `a`.

Only `href` on `a` is retained. No `target`, `style`, event handler, class, id, data attribute, list start attribute, inline image or embed is retained. Links open in the same tab. Legacy `b`/`i` are canonicalized to `strong`/`em`. Known legacy/paste wrappers (div, span, section, article, header, footer, main, font, H1/H4/H5/H6, table/thead/tbody/tr/td/th) are unwrapped on the server; other unknown elements are dropped. Unsupported formatting is removed. Malformed markup is repaired by the maintained parsers/schema, not rejected solely for malformed nesting.

Links allow HTTP, HTTPS, mailto addresses, root/document-relative paths, fragments and query-relative URLs. Absolute HTTP(S) URLs must be valid, have a host, and contain no username/password credentials. Credential-bearing URLs lose their href on both client and server. Protocol-relative URLs (`//...`), backslashes, whitespace/control characters and other schemes (including javascript, data, vbscript, ftp and tel) are removed. Encoded entities are parsed before protocol validation. Invalid links lose `href`; supported text remains. Percent escapes in safe URLs remain URI data.

Scripts, styles, iframes, objects, embeds, SVG, MathML, templates, forms, controls, images, video/audio and noscript content are dropped. Executable HTML widgets are unsupported; images continue through existing media controls. Server sanitation is authoritative regardless of browser behavior. The `HasTranslations` model saving hook sanitizes dirty rich translation fields, including nested records saved through existing services. API HTML is sanitized again on every read, so old unsafe database values cannot become executable output. Direct query-builder imports bypass model events and must use the sanitizer themselves; public rich HTML reads still sanitize.

The server uses a strict element/attribute allowlist and a supplementary link policy. Client DOMPurify provides defense before paste/source is rendered; Tiptap additionally constrains visual markup. Parsers may serialize safe entities differently, but the permitted content and link policy remain the same. Neither output is a general HTML storage mechanism.

HTML containing no safe text (for example only a script, an empty paragraph or `<p><br></p>`) receives field validation feedback through the existing Form Request. Explicitly empty optional fields remain valid. Published legal pages still require both language bodies. Raw input length checks remain in force (20,000 for most fields; 2,000 for summary descriptions; 100,000 for legal/project bodies). The sanitizer has a 500,000-byte limit covering the maximum UTF-8 field input. Unsupported mixed markup is deterministically stripped rather than rejecting a whole otherwise valid article.

## Legacy content and API compatibility

No schema or bulk data migration is needed. Existing CA/ES JSON values and nulls are left in place. Plain text is preserved byte-for-byte in storage and the legacy API text field, including CRLF. The editor keeps its original stored value when no effective edit is made. Blank lines become paragraphs and individual line breaks become `br` in HTML output. Literal plain text is escaped once. Actual existing HTML is parsed/sanitized rather than escaped as literal tags. Edited HTML is stored sanitized in the same JSON locale field.

For every rich field except project `body`, the existing API field remains **plain text**, and a sibling `<field>_html` contains sanitized HTML. For new rich text the plain field is a readable text projection with block/list line breaks and decoded entities. Null values stay null, empty strings stay empty, and existing locale fallback remains unchanged. Safe entity spelling in HTML is not a byte-level API guarantee.

Project detail `body` already had an HTML contract, so it stays HTML; list responses continue omitting it. Project description now adds `description_html` alongside plain `description`. There is no `body_html` alias or global format flag for project bodies. The additional properties are an additive API change; consumers that enforce exact response keys must allow these documented fields.

Existing seeders use first-or-create and only initialize repeatables for newly created parents. They were not changed by this feature and must not be used to overwrite editorial values. The project had pre-existing Portfolio changes, including its migration; those remain separate from this feature. Review/apply outstanding migrations as part of the normal release, not to convert rich text.

## Required Vue integration (separate repository)

Vue has not been modified. Existing interpolation of plain fields continues to work. To show formatting, render the documented `*_html` value with Vue `v-html` inside a dedicated block container; render project detail `body` through the same rich-content presentation. Avoid inserting block markup inside a `p` or heading. For an older backend, use escaped legacy text with preserved whitespace as a fallback; do not pass untrusted plain strings to `v-html`.

Define optional `string | null` TypeScript properties for the new HTML fields during rollout. Add scoped presentation styles for paragraphs, H2/H3, underline, links, lists and blockquotes, including mobile wrapping. Legal pages must consume `body_html` for structured legal headings/lists. Project cards/detail descriptions must consume `description_html`, and detail body must consume `body`. Only use these sanitized API fields as editorial HTML; do not use the raw database/admin input or interpolate editable values into script/style/attributes. Client-side sanitation can be retained as defense in depth. Deploy frontend changes independently after the additive backend contract is available.

## Release procedure

This work does not deploy anything or modify the live editorial database.

1. Back up the database and media using the normal release procedure.
2. Install locked PHP packages with `composer install --no-dev --optimize-autoloader` in PHP 8.5, with the DOM extension available.
3. Install locked frontend packages with `npm ci`; run `npm run build`. Existing Vite font configuration needs access to Bunny during an uncached build.
4. Publish both the backend code and generated `public/build` assets/manifest together. Do not leave a development `public/hot` marker on a production release.
5. Apply only outstanding normal project migrations. This editor adds no migration and requires no content conversion or seeder run.
6. Clear/rebuild normal Laravel view/config caches during release. Verify the admin editor and each API locale.
7. Update Vue separately when ready to use HTML fields. Until then, the public site continues displaying readable plain text.

## Verification

Automated feature tests in `tests/Feature/RichTextCmsTest.php` cover formatting/idempotence, aliases, safe relative/external/mail links, unsafe/encoded protocols, active-content removal, malformed markup, existing form saves, nested records, CA/ES isolation, old input, published legal text, project description/detail contracts, read-time safety for legacy rows, no migration writes and reseeding preservation. Existing CMS authentication/CSRF/save/media suites remain in place. Legal API exact-response expectations now include the additive HTML field.

Run in the project PHP 8.5 container:

```sh
docker compose exec -T app php artisan test --compact
docker compose exec -T app vendor/bin/pint --dirty --format agent
docker compose exec -T app composer validate --strict
npm run build
node --check resources/js/admin-rich-text.js
node --check resources/js/rich-text-policy.js
node --check public/admin-repeatables.js
```

Browser verification uses the local authenticated CMS without saving test edits: exercise every toolbar command and shortcuts; inspect generated source; round-trip supported HTML; paste malicious HTML; create/edit/remove safe links and reject javascript URLs; check URL-dialog focus and Escape; undo/redo; preserve ES while editing CA; add/reorder/remove repeated rows; and inspect the wrapped mobile toolbar. No browser-test dependency was added. Laravel tests cannot prove editor behavior, so these browser checks complement the feature suite.

Verification on 2026-10-12: the full PHP 8.5 suite passed **472 tests / 2,327 assertions**. Pint, strict Composer validation, production Vite build, JavaScript syntax checks and diff whitespace checks passed. Browser checks confirmed functional formatting, Visual/HTML round trips, link validation/focus, malicious HTML paste removal, undo/redo and repeatable add/reorder/remove with preserved text. At a 390px viewport the toolbar wrapped with no horizontal document overflow. No browser errors were captured. Browser test edits were discarded without saving editorial data. Vite reports an existing optional font-fallback optimization warning; it does not prevent the build.


## Portfolio completion verification — 2026-10-12

Portfolio descriptions and bodies reuse this universal component for CA and ES; no second editor was added. `description` remains plain readable text, `description_html` is sanitized HTML, and detail-only `body` remains sanitized HTML. The private admin preview uses the same Resource/read-time sanitation and escapes gallery captions as plain text. Captions/alt do not enable rich HTML.

Browser verification on the existing local authenticated CMS exercised the project create form without saving it: independent CA/ES HTML source → Visual rendering preserved bold/italic, H2/H3, safe links, ordered/unordered lists and blockquotes; the dynamic gallery row exposed independent bilingual caption and alt controls. Temporary browser input was discarded, with no sample project persisted. HTTP tests prove save/read sanitation in both locales, legacy unsafe-row sanitation in private preview and removal of credential-bearing HTTP(S) links.

The complete PHP 8.5 suite now passes 518 tests / 2,540 assertions. Pint, strict Composer validation, Vite build and JavaScript syntax checks pass. The additive WhatsApp migration is unrelated to rich text; no rich-text data migration or seeder execution is needed. The existing Vue Portfolio contract already supports `description_html`, detail `body` and the added plain gallery captions/global WhatsApp, so these additions require approved CMS content rather than another editor/frontend implementation.
