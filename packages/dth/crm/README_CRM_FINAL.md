# DTH CRM Module — C-A → C-I Final

## Source basis
Rebuilt from the CRM domain in `feature/crm-company-lead-opportunity-flow`: Personal/Business Contact Profiles, Company resolution/matching/ownership, Lead intake/activity/distribution, Contact Qualification workflow, Customer conversion/distribution/interactions, Staff availability, and CRM analytics. Sales Opportunity/Quotation/Price Book/Service Catalog remain outside CRM ownership and are exposed only through `SalesHandoffProvider`.

## Recode plan used
- C-A Foundation: package, plugin, config, enums, policy, health command.
- C-B Contact Core: Personal/Business contacts, normalization, deduplication, audience adapter.
- C-C Company Core: normalized company master, tax/domain/name resolution, Company↔Contact, match candidates, ownership.
- C-D Lead Intake: idempotent Marketing intake, lead code, duplicate detection, source/service/attribution snapshots.
- C-E Distribution: staff capacity/availability, least-loaded assignment, accept/reject/reassign history.
- C-F Qualification: New→Assigned→Contacting↔Follow-up→Qualified/Unqualified/Duplicate/Spam→Archived and Sales handoff.
- C-G Customer Core: qualified lead conversion, owner assignment, interactions, batch distribution.
- C-H Integration: Marketing `AudienceProvider` + `LeadProvider`; Sales `SalesHandoffProvider` boundary; no implementation coupling.
- C-I Hardening: policy/RBAC compatibility, bulk authorization, audit model, analytics dashboard, health command.

## End-to-end flow
Marketing Submission → CRM Contact → Company resolution (Business) → Lead → Distribution → Acceptance → Qualification → Sales handoff → Customer lifecycle.

## Ownership boundary
CRM owns Contact, Company, Lead, Qualification, assignment/distribution and Customer relationship state. Sales owns Opportunity, Service/Product/Package, Price Book and Quotation. Finance owns Payment/Revenue. Marketing owns acquisition/submission/UTM. Email owns SMTP/queue/tracking/suppression.
