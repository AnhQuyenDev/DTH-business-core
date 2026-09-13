# Marketing end-to-end business flow contract

This document freezes the cross-module boundary that every Marketing roadmap phase must preserve.

## Business flow

```text
External traffic / Ads / Landing sources
        |
        v
Marketing Campaign + UTM
        |
        v
Landing Page + Form Template
        |
        v
Landing Submission  -- idempotency / anti-duplicate / attribution --> Marketing storage
        |
        +--> AudienceProvider --> CRM Contact/Company capability
        |
        +--> LeadProvider -----> CRM Lead
                                  |
                                  v
                          Lead distribution / acceptance
                                  |
                                  v
                          Qualification / Opportunity
                                  |
                                  v
                         Sales quotation / confirmation
                                  |
                                  v
                           Finance payment / revenue
                                  |
                                  v
                         Official customer / customer care
                                  |
                                  +--> RevenueProvider / CRM signals
                                           |
                                           v
                              Marketing attribution + analytics
```

Email is a parallel Marketing channel, not the owner of the Marketing campaign:

```text
Marketing Campaign
        |
        +--> EmailMarketingBridge --> Email Campaign
                                      SMTP / Queue / Tracking
                                      Suppression / Delivery Log
                                      Email Analytics
```

## Ownership invariants

1. Marketing owns Marketing Campaign, Landing Page, Form Template, Submission, Audience Lists, Segments, UTM, Attribution and Marketing Analytics.
2. Email owns Email Campaign, SMTP, sending queue, open/click tracking, suppression, delivery log and Email Analytics.
3. CRM owns Contact/Company/Lead, lead distribution/acceptance, CRM lifecycle and customer care history.
4. Sales owns service/package catalog, opportunity and quotation lifecycle.
5. Finance owns payment, paid-customer signal and authoritative revenue.
6. Marketing code must not import CRM/Sales/Finance models or Email implementation models. Cross-module calls use contracts/adapters.
7. An unavailable capability is `N/A`; it is never silently converted to zero and unsupported UI choices must not be shown.
8. Backend services enforce lifecycle/scope/idempotency rules even when the Filament UI also hides invalid actions.
9. Adding Marketing must not modify Email send/tracking/suppression behavior. The Email bridge is read/link oriented and is introduced only in M-G.

## Roadmap enforcement points

- M-A: contracts, Null adapters, plugin/i18n/status foundation, no cross-module hard dependency.
- M-B: Campaign lifecycle and service scope guards through `CatalogProvider`.
- M-C: Form mapping is capability-aware; no direct CRM model mapping code.
- M-D: Landing Page service/package selection is sourced through `CatalogProvider`.
- M-E: public acquisition uses idempotency + transaction and hands CRM data to providers.
- M-F: Marketing owns list/segment persistence; provider-supported rules are the only rules exposed in UI.
- M-G: Email integration uses `EmailMarketingBridge`; Email sending/tracking code remains unchanged.
- M-H: revenue/customer metrics are shown only when providers are authoritative.
- M-I: final regression explicitly re-tests the existing Email module and the complete acquisition-to-customer flow.

## Combined M-B -> M-D implementation checkpoint

The urgent combined patch now implements the flow up to, but not including, public acquisition processing:

```text
Marketing Campaign (M-B)
    -> Personal/Business Form Templates (M-C)
    -> Landing Page + service/package context (M-D)
    -> Published GET /lp/{slug}
    -> [STOP: form controls are preview-only in M-D]
    -> M-E will add POST submission, idempotency, UTM capture and CRM/Lead handoff
```

The `Generate UTM` M-D action builds a non-persisted URL for page operations. Ownership of persisted UTM links, tracking/reporting and Email integration remains M-G.

## M-H Analytics and M-I hardening extension

The end-to-end flow remains unchanged. M-H reads deterministic facts produced by the existing flow; it does not become a new owner of CRM, Sales, Finance or Email data.

```text
Traffic / UTM / Email link
        -> Landing Page View
        -> Form Submission
        -> Semantic normalization
        -> optional CRM Contact / Lead contract
        -> optional Finance RevenueProvider summary
        -> Marketing Analytics / Campaign Report
        -> PDF / XLSX / CSV
```

Capability rule:

- Marketing-owned Views/Submissions are always authoritative.
- Lead KPIs are N/A without `LeadProvider`.
- Customer/Revenue/ROAS are N/A without complete `RevenueProvider` data.
- Email performance is read through `EmailMarketingBridge`; Email remains owner of send/tracking.

M-I wraps the same flow with rate limits, honeypot, idempotency, duplicate protection, payload ceiling, RBAC and audit. These controls do not change the stable Landing/Form rendering contract.
