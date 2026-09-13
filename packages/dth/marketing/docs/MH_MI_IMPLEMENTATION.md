# M-H / M-I Implementation Notes

## Roadmap alignment

The implementation follows the final Marketing roadmap:

- M-G: UTM plus Email integration via bridge only.
- M-H: deterministic analytics, campaign reporting, attribution, statistical insights, PDF/XLSX/CSV.
- M-I: authorization, public acquisition hardening, analytics indexes, audit trail, capability health, regression/final UAT.

## Analytics ownership

Marketing computes only data it owns or receives through contracts.

Marketing-owned facts:

- Landing Page views.
- Form submissions.
- UTM dimensions.
- Marketing Campaign budget/scope.
- Landing Page and Campaign relationships.
- Email link snapshots copied through the bridge.

Capability-owned facts:

- Lead conversion requires `LeadProvider`.
- Paid customers and revenue require `RevenueProvider`.
- Email delivery/open/click metrics are snapshots exposed through `EmailMarketingBridge`.

Missing capability is represented as `N/A`, never as a fabricated zero.

## Filters

`MarketingAnalyticsFilter` uses GET parameters and produces an immutable, bounded date range. Presets are 7d, 30d, 90d and current month. Custom ranges are capped by `dth-marketing.analytics.max_custom_days`.

All dashboard and Campaign Report export URLs carry the same filter query string used by the screen.

## Acquisition source consistency

M-E originally stored a normalized acquisition source on submissions while views stored UTM/referrer only. M-I adds `marketing_landing_page_views.source` and populates it with the same precedence used by runtime tracking:

1. `utm_source`
2. Landing Page `tracking_source`
3. referrer host
4. `direct`

This prevents mismatched View -> Submission source attribution.

## Report export design

`MarketingReportExportService` receives already-filtered analytics data. It does not re-query with different filters.

- CSV: UTF-8 BOM, sectioned deterministic export.
- XLSX: minimal valid Office Open XML workbook with separate worksheets.
- PDF: deterministic multi-page text summary suitable for operational sharing.

No CRM/Sales/Finance/Email model is referenced by the exporter.

## Authorization design

`MarketingResourcePolicy` provides resource CRUD protection. Custom Filament actions perform explicit checks through `MarketingAuthorizationService` because Filament does not automatically authorize arbitrary custom actions.

Bulk delete for Campaign, Landing Page and Form Template uses per-record authorization so lifecycle/business delete guards cannot be bypassed by a bulk operation.

## Audit design

`MarketingAuditTrailService` is append-only from business flows. It deliberately excludes user-submitted PII fields from audit payloads and stores only an HMAC of the request IP.

Audit insertion is fail-open for the business transaction so installing/upgrading the package before migration does not break stable Campaign/Landing/Form flows.

## Integration health

`IntegrationHealthService` treats missing optional providers as `N/A`. Provider exceptions are surfaced as health `ERROR` without making the capability card claim valid data.

## Stable renderer boundary

M-H/M-I do not change the confirmed source-parity Landing Page/Form Template renderer or HTML import pipeline. This is a hard non-regression boundary after the prior UAT fixes.
