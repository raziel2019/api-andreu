# Legal CMS — Phase 3

## Domain and languages

`LegalPage` uses `legal_pages`: id, unique constrained type, JSON title, nullable JSON body, active (false by default), timestamps. Types: `legal_notice`, `privacy_policy`, `cookie_policy`. There are no Terms pages: the current product is informational/contact-oriented. Blog remains absent.

Translations contain only `ca` and `es`; Catalan is canonical/default. The existing translation concern, locale middleware and API Resources are reused. No table per language, English, dependency, Vue modification, SEO, deployment or cookie banner is introduced.

## Editing and publication

Navigation offers Avís legal, Privacitat and Cookies. GET/PUT `/admin/legal/legal-notice`, `/admin/legal/privacy`, `/admin/legal/cookies` require the existing authenticated admin Gate; forms include CSRF protection. Each editor has separate CA/ES title and body fields and a visibility checkbox, using existing Blade components and the SaveContent transaction service.

Content is plain text with newline-separated paragraphs, not HTML or Markdown. Titles are required in both languages (255 characters). Both body keys must be present; bodies can be null in inactive drafts (100,000 characters maximum each). Activation requires nonempty bodies in both languages. HTML tags are rejected in title/body; Blade escapes stored values and failed-input redisplays. Do not render the API body through `v-html` or an HTML/Markdown interpreter. Render as text, using `white-space: pre-wrap` or escaped paragraph nodes to preserve formatting.

Activation publishes immediately; deactivation withdraws immediately. Missing/inactive records return JSON 404. Activation is an editorial decision: the application checks completeness, not legal accuracy, and cannot certify client review. The administrator must confirm the facts and review both texts before publishing. Settings are not automatically interpolated into the body; after identity changes, review all affected legal texts.

LegalPageSeeder creates only the three bilingual titles with null bodies and inactive status. `firstOrCreate` preserves existing content and visibility on reseeding. No placeholder body is published. Existing upgraded databases should run migrations and this seeder to provision the editors; fresh installs use DatabaseSeeder.

## Identity configuration

The existing `/admin/settings` editor adds nullable private fields: `legal_name`, `tax_identifier` (a string, no unconfirmed NIF format assumption), `postal_address`, `privacy_contact_email`, `professional_registration`, `data_controller_name`. A separate postal address is necessary because existing translated `location` is only public location copy. Use the existing `contact_email` and `phone`; leave the privacy email blank when the general address applies. Leave controller name blank when it is the same legal owner and reuse that name when composing policy text. Empty inputs normalize to null. These new fields do not appear in the public SiteSetting resource or legal resource. Facts intended for publication must be included deliberately in the legal body.

No NIF, postal address, registration, controller identity, hosting/provider details, retention period, processor, international transfer, analytics or marketing-cookie declaration is invented. All legal-specific fields start null. Provider details can be included in reviewed editorial content if the client supplies them; no speculative provider schema is added.

## Content preparation for client review

Bodies support headings, paragraphs and textual lists for these subjects; no assertion is seeded:

- Legal Notice: owner/provider identity, contact and tax identification, intellectual property, liability, applicable law/jurisdiction.
- Privacy: controller, purposes, legal basis, data categories, recipients/processors, transfers if applicable, retention, data-subject rights, complaint authority and contact method.
- Cookies: actual cookie/local-storage inventory, purpose, category, provider, duration and consent requirements.

The client must confirm applicable identity/contact facts, actual processing practices, recipients, transfers, retention, complaint authority and legal wording. Confirm applicability of professional registration, governing law/jurisdiction and provider details before including them. Complete and review the Catalan source and Spanish translation before activation.

## Frontend integration contract

Read-only GET endpoints:

- `/api/v1/legal/legal-notice`
- `/api/v1/legal/privacy`
- `/api/v1/legal/cookies`

Optional `?locale=ca` or `?locale=es`; omitted locale uses ca. Unsupported/empty/array locales receive JSON 422. Responses include Content-Language and `{"data":{"type":"privacy_policy","title":"localized title","body":"localized plain text"}}`. No raw translation objects, identity settings or admin metadata are exposed. Existing missing-Spanish fallback behavior uses Catalan. The frontend should handle 404 as unavailable and never substitute a draft template.

## Later cookie-consent work

This phase does not inspect or change Vue or implement consent. Inventory actual cookies and local preference storage before drafting final declarations. Do not assume analytics or marketing tools exist. Future frontend work must determine which technologies require consent and, where applicable, block them until consent, support withdrawal and keep the inventory/policy current. A legal content endpoint itself provides no consent enforcement.

## Verification

LegalCmsTest covers the three admin/API flows, guest/non-admin protection, locale selection/default, inactive/missing 404, no Terms/Blog endpoint, read-only API, separate persisted translations, draft/activation/deactivation behavior, HTML rejection and escaped legacy text, unknown translation keys, limits, legal identity validation/privacy, and nondestructive seeding. Run the full existing suite for regression coverage.
