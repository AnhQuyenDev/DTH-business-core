# Marketing M-E -> M-G implementation

Target: PHP 8.2+ / Laravel 12 / Filament 4.13.1.
Baseline: Marketing M-B -> M-D Hotfix V3.

This delivery is deliberately additive. It enables the public acquisition, audience/segment, UTM and Email bridge layers while preserving the M-B -> M-D campaign, HTML import, visual preview, lifecycle, hard-delete and Copy Link behavior.

## M-E - Public Submission Pipeline

### Public flow

```text
Visitor
  -> GET /lp/{slug}
  -> record marketing_landing_page_views + UTM/referrer
  -> rendered Personal/Business Form Template keeps V3 presentation
  -> POST /lp/{slug}/submit
  -> dynamic Form Template validation
  -> UUID submission_token + payload fingerprint
  -> Cache::lock()
  -> DB::transaction() persist Received submission
  -> honeypot spam check
  -> AudienceProvider (optional)
  -> LeadProvider (optional)
  -> post-submission automation
  -> Processed | Failed | Spam
  -> redirect URL or thank-you page
```

### Anti-duplicate / idempotency

- A rendered public form receives a UUID `_submission_token`.
- `landing_page_id + submission_token` is unique.
- The same token may return the same persisted submission only when the payload fingerprint also matches.
- Reusing a token for different data is rejected.
- A SHA-256 payload fingerprint also protects the no-token/double-click path for a short window.
- `Cache::lock()` protects concurrent requests and persistence is wrapped in `DB::transaction()`.

### Submission states

- `received`: stored before cross-module integration.
- `processed`: Marketing finished the supported integration/automation path.
- `failed`: data stays available with `failure_reason` and can be retried by an administrator.
- `spam`: honeypot or administrator classification.

The public controller does not display a success confirmation for a `failed` submission. The thank-you route also requires the Landing Page to remain `Published`.

### CRM boundary

Marketing does not import CRM Contact/Lead models. It calls only:

- `AudienceProvider::upsertContact()` / `addTags()`
- `LeadProvider::createOrUpdateFromMarketing()`

External Contact and Lead identifiers are stored as string references. If CRM is absent, the submission pipeline still boots and persists data; CRM fields remain unavailable/N/A rather than producing fake values.

### Administration

`LandingPageSubmissionResource` provides:

- filters by Landing Page, type and status;
- full payload/attribution/integration snapshot view;
- Retry for `failed` / `received`;
- Mark Spam;
- bulk Retry Failed and bulk Mark Spam.

## M-F - Marketing Lists, Segments and automation

### Marketing Lists

Marketing owns:

- `marketing_contact_lists`
- `marketing_contact_list_members`

Members can come from a processed Landing submission or be entered manually. Membership uses a stable `member_key` based on audience type + normalized email/phone where possible. List/member tables include bulk operations.

### Post-submission automation

Landing Page and Form Template can optionally configure:

- fixed tags;
- fixed Marketing List names;
- auto-created Landing Page segment.

The original Marketing behavior `tag_from_value` is also restored as an additive field option: a Form field may promote its submitted value to a tag. Tags are stored on the Marketing submission for segmentation and, when available, forwarded through `AudienceProvider`; Marketing does not import a CRM Tag model.

### Segment engine

`SegmentRuleRegistry` is capability-aware. A rule not supported by the installed backend is not exposed by the UI and backend validation rejects it instead of silently ignoring it.

Local rules implemented for M-F:

- `created_within_days`
- `created_between`
- `has_tag`
- `in_list`
- `customer_type`
- `service_interest`
- `source`
- `utm_source`
- `utm_campaign`
- `landing_page`

`lead_status` appears only when `LeadProvider` is available.

All configured conditions are ANDed. Segment actions provide preview count and sample before/after save. Automatic Landing Page segments are protected from manual editing.

## M-G - UTM and Email integration

### UTM

`LandingPageUtmService` persists generated links in `marketing_landing_page_utm_urls` with:

- source
- medium
- campaign
- content
- term
- Landing Page / Marketing Campaign relation

The Landing Page row action now persists generated UTM links. The UTM report groups captured views/submissions by source/medium/campaign and includes the generated-link history. Full cross-campaign analytics remain M-H.

### Email bridge

Marketing Campaign -> Email Campaign integration goes only through `EmailMarketingBridge`.

`DthEmailMarketingBridge` is an optional adapter under `Integrations/Email`; this is the only Marketing layer that knows DTH Email implementation class names. It is read/navigation only:

- discover Email Campaigns whose `source_type/source_id` points at a Marketing Campaign;
- resolve the Email Campaign admin page;
- refresh Marketing-owned link snapshots.

Manual Email Campaign references are also supported so linking does not require changing Email Campaign sending logic.

Explicitly out of scope for this bridge:

- SMTP
- queue sending
- suppression
- open/click tracking writes
- Delivery Log writes
- Email Campaign lifecycle mutation

Those remain owned by Email Module.

## Database added after M-D Hotfix V3

- `marketing_landing_page_views`
- `marketing_landing_page_submissions`
- `marketing_contact_lists`
- `marketing_contact_list_members`
- `marketing_segments`
- `marketing_landing_page_utm_urls`
- `marketing_campaign_email_links`
- additive automation columns on Landing Page / Form Template
- additive `tag_from_value` on Form fields

Migrations `000001` through `000007` from the V3 baseline are unchanged byte-for-byte.

## Stable M-B -> M-D surfaces protected

The following V3 files were checksum-compared and are unchanged:

- `HtmlFormParser.php`
- `FormTemplateHtmlImportService.php`
- `LandingPageHtmlImportService.php`
- `FormTemplatePreviewService.php`
- `FormTemplateLifecycleService.php`
- `LandingPageLifecycleService.php`
- `MarketingCampaignLifecycleService.php`
- `LandingPageCatalogService.php`
- `resources/views/filament/copy-link.blade.php`
- migrations `000001` -> `000007`

`LandingPageRenderService` is intentionally extended only for the public M-E submit mode. Preview still calls the stable V3 Form Template renderer with disabled controls, and the imported Form Templates remain appended at the end of the Landing Page with their presentation assets.

`FormTemplateResource` and `LandingPageResource` receive only additive M-F/M-G controls/actions. Their import/preview/lifecycle implementation is not replaced.
