# Portfolio API contract — Laravel

Verified 2026-10-12 against Laravel controllers/Resources and `../Frontend/docs/portfolio-api-contract.md`. This documents the existing public API plus additive gallery caption and global WhatsApp fields. Vue was not modified.

## Transport, availability and locale

Public read endpoints use `/api/v1`, accept `locale=ca|es`, default to CA, and return `Content-Language`. Invalid locale values, including arrays/empty values, receive 422. Spanish missing/null translation values fall back to CA; explicit empty strings stay empty. Translated API fields are localized scalar strings/null, never CA/ES objects. Public requests require no credentials. Existing CORS configuration remains in use.

`GET /api/v1/projects` and `GET /api/v1/projects/{slug}` return 404 if singleton Portfolio page 1 is missing, inactive, or `under_construction=true`. Missing/draft slugs return 404. No draft/status/body fields are added to listing responses. Global site `pages.portfolio=false` reflects inactive Portfolio and allows Vue to suppress Portfolio requests.

## Exact listing and detail shapes

```ts
type Locale = 'ca' | 'es'

type PortfolioCategory = {
  id: number
  slug: string
  title: string | null
  sort_order: number
}

type ProjectSummary = {
  id: number
  slug: string
  title: string | null
  description: string | null       // readable plain text
  description_html: string | null  // server-sanitized HTML
  category: PortfolioCategory | null
  featured_image_url: string | null
  featured_image_alt: string | null
  year: number | null
  client: string | null
  sort_order: number
}

type GalleryImage = {
  url: string | null
  alt: string | null               // accessible localized text
  caption: string | null           // localized plain caption; new additive field
  sort_order: number
}

type ProjectDetail = ProjectSummary & {
  body: string                    // sanitized localized HTML; absent/null becomes ''
  gallery: GalleryImage[]
}

type PortfolioPage = {
  id: number
  listing_title: string | null
  eyebrow: string | null
  introduction: string | null
  introduction_html: string | null
  callout_title: string | null
  callout_content: string | null
  callout_content_html: string | null
  related_title: string | null
  related_eyebrow: string | null
  whatsapp_label: string | null
  whatsapp_url: string | null      // existing page-level compatibility URL
  title: string | null
  construction_label: string | null
  message: string | null
  message_html: string | null
  cta_label: string | null
  cta_destination: string | null
  linkedin_destination: string | null
  under_construction: boolean
}

type ProjectListingResponse = {
  data: ProjectSummary[]
  meta: {
    locale: Locale
    page: PortfolioPage
    categories: PortfolioCategory[]
    current_page: number
    last_page: number
    total: number
  }
}

type ProjectDetailResponse = {
  data: ProjectDetail
  meta: {
    locale: Locale
    page: PortfolioPage
    related_projects: ProjectSummary[]
  }
}
```

List: `GET /api/v1/projects?locale=ca|es&page=1&category={category-slug}`. Omit category for all published projects. Pagination is 24 per page; `page` must be a positive integer, category must be a string up to 100 characters. Invalid shapes return 422. Unknown category produces an empty list, not 404. An empty result has total 0 and last_page 1. No top-level pagination links are emitted by this controller.

Projects order by `sort_order`, then ID. Category metadata includes all categories with at least one published project, independent of the selected filter, ordered by category `sort_order`, then ID. Draft-only/unused categories are omitted. Nullable/deleted categories remain null on projects. Categories are eagerly loaded; query count does not grow with the number of returned projects.

Detail: `GET /api/v1/projects/{slug}?locale=ca|es`. Slugs use lowercase ASCII letters/digits separated by hyphens, max 100 characters. No `body_html` alias exists. Gallery is sorted ascending by `sort_order`; equal order preserves entry order. Legacy galleries without caption return `caption:null`; missing/null Spanish captions and alt values fall back to CA. Explicit empty strings remain empty. Caption never falls back to alt text. Gallery and body appear only on detail; related entries retain the summary shape.

Related projects: at most three published projects, excluding the current ID, prioritizing its category, followed by `sort_order`, then ID. If the current project has no category, the existing query applies the normal order without a category match. No status, raw translation maps, storage paths, media IDs, timestamps or internal settings are exposed by project Resources.

`GET /api/v1/pages/portfolio` retains `{data:PortfolioPage}`. Its existing controller rejects inactive pages, but continues returning the construction-state page when under construction. Project endpoints reject construction mode as described above. Empty listing is a valid 200 when the page is available and no published projects match.

## Exact site shape and WhatsApp additions

`GET /api/v1/site?locale=ca|es` retains the existing wrapper and all fields:

```ts
type SiteResponse = {
  data: {
    id: number
    pages: {
      home: boolean
      about: boolean
      services: boolean
      portfolio: boolean
      contact: boolean
      legal_notice: boolean
      privacy: boolean
      cookies: boolean
    }
    theme: { primary_color: string; background_color: string }
    location: string | null
    callout_title: string | null
    callout_content: string | null
    callout_content_html: string | null
    callout_label: string | null
    footer_description: string | null
    footer_description_html: string | null
    footer_cta_label: string | null
    footer_copyright: string | null
    contact_email: string | null
    phone: string | null
    whatsapp_number: string | null  // new: canonical international number
    whatsapp_url: string | null     // new: derived HTTPS wa.me URL
    linkedin_url: string | null
    instagram_url: string | null
    interface_media: {
      action_arrow: string | null
      service_arrow: string | null
      footer_arrow: string | null
      contact_callout: string | null
      contact_message: string | null
      contact_location: string | null
      menu_open: string | null
      menu_close: string | null
      email: string | null
      linkedin: string | null
      instagram: string | null
    }
    logo_url: string | null
    callout_destination: string | null
    footer_cta_destination: string | null
  }
}
```

Default additive values:

```json
{
  "whatsapp_number": "+34694229352",
  "whatsapp_url": "https://wa.me/34694229352"
}
```

The snippet belongs at `data`, not at response root. Both values are nonlocalized technical fields. The database default is configured by the new settings migration; the Resource derives the URL from the saved number and does not hardcode a fallback. Null/invalid numbers produce null URLs. Existing `phone` remains independent. Save format and normalization are documented in [Portfolio CMS](portfolio-cms.md#whatsapp-and-migration).

The inspected Vue contract already accepts optional `site.whatsapp_url` and gallery caption. Its precedence is safe global URL → safe page URL → its existing authorized default. Clearing the global number does not turn off those frontend fallback links. Page `whatsapp_url` is preserved for old consumers; no new public endpoint is required. Consumers should allow additive fields during rollout.

## Rendering boundaries

Media URLs reuse `CmsMedia::publicUrl`; public disk paths become storage URLs on the configured backend origin. Existing absolute HTTP(S) references remain compatible. Missing values are null. Gallery captions/alt are plain text and must be escaped/interpolated independently.

Rich HTML allows `p`, `br`, `strong`, `em`, `u`, `h2`, `h3`, `ul`, `ol`, `li`, `blockquote`, `a`; only `a[href]` survives. Scripts/embeds/inline images/styles/event handlers are dropped. Links allow safe HTTP(S), mailto, relative paths, fragments and queries; credentials, protocol-relative URLs, backslashes, controls and unsafe schemes are removed. Server sanitation runs at save and read, including legacy rows. Render only `description_html`, detail `body` and documented page HTML fields as HTML. Continue using escaped plain text as older-backend fallback. See [rich text policy](rich-text-editor.md).

## Tests and integration

Existing and new feature tests verify exact list/detail keys, locales, filtering/category metadata, gallery translation/fallback/order, related exclusions, visibility/construction, publish transitions, sanitization and bounded list queries. WhatsApp tests cover the database default, normalization, persistence, omission/clearing, API output, invalid values and permissions. Populated responses use isolated test fixtures; no fake records are seeded into real work.

Manual local steps and all admin routes/migrations are documented in [Portfolio CMS](portfolio-cms.md#verification-and-local-end-to-end-workflow). The separate Vue project requires approved project content/media and the intended CMS visibility/construction settings; its existing API clients already support these additions.
