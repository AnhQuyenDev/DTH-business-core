# Source parity - Marketing M-E -> M-G

This document records what was recovered from the original Marketing source and what was deliberately redesigned to obey the new package ownership rules.

| Original Marketing capability | M-E -> M-G package implementation | Decision |
|---|---|---|
| Landing Page GET view tracking | `LandingPageTrackingService` + `marketing_landing_page_views` | Rebuilt |
| UTM source/medium/campaign/content/term capture | View + submission attribution columns | Rebuilt |
| `submission_token` | UUID token generated in public form | Rebuilt |
| `payload_fingerprint` | Deterministic SHA-256 fingerprint | Rebuilt |
| `Cache::lock()` concurrency guard | `LandingPageSubmissionService` | Rebuilt |
| `DB::transaction()` around acquisition persistence | Received submission persistence | Rebuilt |
| Duplicate Contact / Lead work | `AudienceProvider` + `LeadProvider` | Redesigned: no direct CRM model dependency |
| Submission states / failure audit | Received / Processed / Failed / Spam + failure reason | Rebuilt |
| Fixed auto tags | Submission tags + optional `AudienceProvider::addTags()` | Rebuilt through contract |
| Form-field `tag_from_value` | Additive Form field option, value promoted to Marketing tag | Restored |
| Auto list membership | Marketing-owned Contact List + member upsert | Rebuilt |
| Auto segment after Landing submission | Automatic Landing Page segment | Rebuilt |
| Contact list CRUD/member management | `ContactListResource` + member relation manager | Rebuilt |
| Segment rule engine | `SegmentRuleRegistry` + `SegmentQueryService` | Rewritten capability-aware |
| Segment count/sample preview | Filament actions | Rebuilt |
| UTM generator | `LandingPageUtmService` | Rebuilt |
| Stored UTM URLs | `marketing_landing_page_utm_urls` | Rebuilt |
| UTM views/submissions report | `UtmReportService` | Rebuilt for M-G; full analytics deferred to M-H |
| Email sender/campaign code inside old Marketing | Not copied | Removed from Marketing ownership |
| Marketing -> existing Email Campaign relation | `EmailMarketingBridge` + `marketing_campaign_email_links` | Rebuilt as bridge |
| Open Email Campaign from Marketing | Email admin URL from bridge | Rebuilt |
| Email SMTP / queue / open-click / suppression / delivery | No implementation in Marketing | Intentionally remains Email Module |

## Important redesign boundary

The original submission service directly referenced CRM models and CRM services. That dependency is intentionally not copied. The new package persists Marketing-owned acquisition records first, then calls contracts. This keeps the end-to-end business flow available when CRM is installed while allowing Marketing to boot and retain submissions when CRM is absent.

The original Marketing source also mixed Email campaign responsibility into Marketing. M-G does not restore that coupling. The new Email bridge is read/navigation/linkage only and does not change Email sending or tracking behavior.
