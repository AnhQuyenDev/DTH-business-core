# Old CRM source audit

Scanned source: `feature/crm-company-lead-opportunity-flow/marketing-email-laravel-v12`.

## CRM models found (22)
BusinessContactProfile, Company, CompanyAssignment, CompanyContact, CompanyMatchCandidate, ContactQualification, ContactQualificationNote, Customer, CustomerAssignment, CustomerDistributionBatch, CustomerDistributionItem, CustomerInteraction, CustomerList, Department, LandingPageForm, Lead, LeadActivity, PersonalContactProfile, Position, Staff, StaffAvailability, StaffBusinessFunction.

## CRM services found (22)
CompanyCodeGenerator, CompanyContactLinkService, CompanyMatchReviewService, CompanyNormalizationService, CompanyOwnershipService, CompanyResolutionService, CompanyTaxVerificationSyncService, ContactQualificationWorkflowService, CustomerCareEmailService, CustomerCareService, CustomerDistributionService, CustomerExportService, CustomerImportService, FakeTaxVerificationProvider, LeadActivityService, LeadAssignmentService, LeadCodeGenerator, LeadCreationService, LeadDistributionService, LeadFormAnswerSnapshotService, SegmentQueryService, TaxCodeVerificationService.

## Important source behavior retained
- Personal and Business contact profiles are distinct.
- Business submissions resolve Company by tax code first, business email domain second, normalized company name as review candidate.
- Lead is its own intake entity, not an Opportunity.
- Lead assignment respects staff eligibility/capacity and Company Account Owner ownership.
- Qualification has an explicit state machine and backend validation.
- Activities and follow-up dates update qualification work state.
- Customer ownership/distribution and interaction history are first-class CRM concepts.
- V2 source deprecates manual Contact→Customer conversion when paid-only business flow is enabled; final Customer is created from a paid Sales opportunity. The recode preserves this boundary.

## Source behaviors intentionally delegated
`CustomerList` and CRM `SegmentQueryService` referenced Marketing-owned audience tables; final Marketing M-F owns them. `LandingPageForm` is Marketing-owned. `CustomerCareEmailService` imports Email implementation classes; Email stays the owner of sending. Department/Position/RBAC are organization/core configuration. Opportunity/Quotation remain Sales. Payment/Revenue remain Finance.
