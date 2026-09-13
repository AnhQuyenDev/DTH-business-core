# DTH CRM Recode Plan C-A → C-I (As-built)

This plan was derived by scanning the complete old CRM domain under `feature/crm-company-lead-opportunity-flow`, including 22 CRM models, 22 CRM services, CRM enums, migrations, actions/events and Filament resources.

## Source ownership decisions

### Kept inside CRM
- Personal / Business Contact identity and profile data.
- Company master, Company ↔ Contact relationship, matching candidates and account ownership.
- Lead intake, source/service/attribution snapshots, deduplication, assignment/distribution and activity timeline.
- Lead Qualification workflow and follow-up.
- Customer master after conversion, assignment, distribution and interaction timeline.
- Staff capacity/availability required for CRM distribution.
- CRM analytics, policy/authorization and audit storage.

### Moved behind module boundaries instead of copied into CRM
- Marketing Contact Lists/Segments/Landing Page/Form data → Marketing owns them.
- Email sending/customer-care delivery → Email owns SMTP/account/template/queue/tracking; CRM only owns customer interaction state.
- Opportunity/Quotation/Price Book/Service Catalog → Sales owns them. CRM exposes `SalesHandoffProvider`.
- Payment/Revenue → Finance owns them. CRM customer creation defaults to paid-only semantics and exposes `convertFromPaidSales()` for the future Sales/Finance flow.
- Department/Position/RBAC organization configuration → Core/Organization owns them. CRM keeps only the staff projection needed for lead/customer assignment.

## C-A Foundation
Package/service provider/config/plugin, enums, policies, health command, CRM navigation, prefixed `crm_*` schema.

## C-B Contact Core
Personal + Business contacts, normalization, contact-code generation, email/phone deduplication, tags and Marketing AudienceProvider adapter.

## C-C Company Core
Company normalization, tax-code/domain/name resolution, free-email-domain protection, Company↔Contact, matching candidates, ownership/assignments and optional tax verification contract.

## C-D Lead Intake + Deduplication
Idempotent submission reference, Lead code, recent-contact/service duplicate detection, Marketing attribution/form answer snapshot, automatic Qualification row.

## C-E Lead Distribution + Acceptance
Staff availability/capacity, Company Account Owner precedence, round-robin/least-loaded/weighted/manual strategies, assignment history, accept/reject and re-queue.

## C-F Qualification + Activities
Backend state machine, first/last contact, follow-up date, budget/timeline/service validation, Qualified handoff to Sales adapter, Lead activities and qualification notes model.

## C-G Customer Core
Paid Sales conversion boundary, customer ownership, customer distribution batches/items, customer interactions, care statistics, CSV import/export services.

## C-H Integrations
- Marketing `AudienceProvider` → CRM Contact.
- Marketing `LeadProvider` → CRM Lead; Business submissions resolve Company without requiring Marketing to know CRM schema.
- CRM `SalesHandoffProvider` → future Sales module; null adapter keeps CRM standalone.
- `TaxVerificationProvider` → optional provider; null adapter keeps company workflow usable.

## C-I Hardening
Filament v4 resources, per-record authorization for bulk delete, `crm.view`/`crm.manage` compatibility, no direct Email/Finance/Sales implementation imports, audit table, health command and final UAT gate.

## Business flow preserved
`Marketing Submission → Contact → Company resolution (Business) → Lead → Distribution → Acceptance → Qualification → Sales Handoff → Paid Sale → Customer → Customer Care`.
