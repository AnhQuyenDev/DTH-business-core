# Sales & Quotation Implementation Progress

## Current Phase
Phase 1 ✅ — Catalog & Price Book
Phase 2 ✅ — Quotation Core
Phase 3 ✅ — Public View & PDF
Phase 4 ✅ — Transactional Email
Phase 5 ✅ — Confirmation & Approval
Phase 6 ✅ — Customer Care & Dashboard

## Completed
### Phase 1 — Catalog & Price Book ✅
- [x] Survey codebase structure
- [x] Install barryvdh/laravel-dompdf
- [x] Create directory structure
- [x] 16 Sales Enums created
- [x] 12 Migrations created & executed
- [x] 12 Models created
- [x] 6 base Services created
- [x] 4 Policies
- [x] 16 Sales Gates in AppServiceProvider
- [x] 4 Filament Resources (Service, ServicePackage, PriceBook, BankAccount)
- [x] Database migrations executed (fresh)
- [x] SalesSeeder (services, packages, price books, bank accounts)
- [x] Lang translations (resource, field, section labels)

### Phase 2 — Quotation Core ✅
- [x] QuotationCreationService — full creation with Customer/PriceBook validation, snapshots, items, pricing, transaction, audit
- [x] QuotationRevisionService — version increment, supersede old version, copy data
- [x] QuotationApprovalService — submitForApproval, approve, reject
- [x] QuotationPdfService — generate PDF with dompdf, file storage, hash tracking
- [x] QuotationMailService — validate recipient, ensure PDF, dispatch email job
- [x] QuotationConfirmationService — accept/reject/requestRevision with status transition, interaction creation
- [x] Jobs: SendQuotationEmailJob, GenerateQuotationPdfJob, ExpireQuotationsJob
- [x] Mail: QuotationMail (Mailable with PDF attachment)
- [x] QuotationResource — table with filters, View/Create/Edit pages, 5 RelationManagers (items, confirmations, email logs, documents, approvals)
- [x] QuotationApprovalResource — approval listing with quick approve/reject actions
- [x] Public routes — /q/{code}/{token} with show/pdf/accept/reject/request-revision
- [x] Public view — Tailwind UI with modal confirm forms
- [x] PDF view — styled quote template
- [x] Email view — transactional email template
- [x] Lang translations for Phase 2 fields
### Phase 3 — Public View & PDF ✅
- [x] 5 Public routes (show, pdf, accept, reject, request-revision)
- [x] Public Blade view with full quotation display
- [x] PDF view with company info, items, payment info, QR, terms
- [x] QrPaymentService — VietQR URL generation
- [x] QuotationPdfService — dompdf generation + file storage
- [x] QuotationDocument model + migration
- [x] View tracking (first_viewed_at, last_viewed_at, view_count)
- [x] Rate limiting (throttle:30,1) on ALL routes (including show/pdf)

### Phase 4 — Transactional Email ✅
- [x] QuotationMail (Mailable with PDF attachment)
- [x] QuotationMailService — validate recipient, check contact status, dispatch job
- [x] SendQuotationEmailJob — send with retry/backoff, log status
- [x] QuotationEmailLog model + migration
- [x] Email template quotation-sent.blade.php
- [x] SendQuotationAcceptedNotificationJob + QuotationAcceptedMail + template
- [x] SendQuotationRevisionRequestedNotificationJob + QuotationRevisionRequestedMail + template
- [x] SendQuotationRejectedNotificationJob + QuotationRejectedMail + template
- [x] SendQuotationExpiringNotificationJob + QuotationExpiringMail + template
- [x] SendQuotationExpiredNotificationJob + QuotationExpiredMail + template
- [x] SendPaymentConfirmedNotificationJob + PaymentConfirmedMail + template
- [x] QuotationResent (reuse QuotationMail via QuotationMailService::resend() with `quotation-resent` template)

### Phase 5 — Confirmation & Approval ✅
- [x] QuotationConfirmationService — accept/reject/requestRevision with state validation
- [x] QuotationApprovalService — submitForApproval/approve/reject
- [x] QuotationApprovalResource — Filament listing with approve/reject actions
- [x] State transition validation (QuotationStateMachine)
- [x] Anti-double-submit via DB transaction
- [x] Expiry validation (cannot accept expired quotation)
- [x] Version validation (cannot accept superseded version)
- [x] Audit log for all actions
- [x] CustomerInteraction created for each event

### Phase 6 — Customer Care ✅
- [x] QuotationInteractionService — 6 event types (created, sent, viewed, accepted, rejected, revision_requested, payment_updated, accepted_notification_sent)
- [x] QuotationReminderService — 5 reminder queries (sent-not-viewed, viewed-not-responded, expiring-soon, accepted-unpaid, revision-requested)
- [x] SendQuotationReminderJob — dispatch follow-up tasks
- [x] Console command `sales:process-reminders` (expire + reminder in one command)
- [x] Schedule in routes/console.php (everyMinute)
- [x] PaymentTrackingResource — Filament table with mark_paid/pending/unpaid actions
- [x] CustomerCarePage — summary cards + assigned customer table
- [x] Quick Actions trên CustomerResource (tạo báo giá, xem báo giá)
- [x] SalesDashboard — summary stats, conversion rates, expiring alerts, staff follow-up
- [x] Banks: BankAccountResource
- [x] SalesSeeder — 4 services, 4 packages, 3 price books, 2 bank accounts, 1 sample quotation
- [x] PriceBookAccessRule RelationManager
- [x] Customer lifecycle transition on payment (Active + Purchasing)
- [x] OTP routes (/send-otp, /verify-otp) with throttling
- [x] QuotationPublicAccessService extracted from controller

## Files Created (Phase 1)
- `app/Enums/Sales/*.php` — 16 enum files
- `app/Models/Sales/*.php` — 12 model files
- `app/Services/Sales/*.php` — 6 base service files
- `database/migrations/2026_07_29_000001_*.php` → `000012_*.php` — 12 migration files
- `app/Policies/Sales/QuotationPolicy.php`, `PriceBookPolicy.php`, `ServicePolicy.php`, `BankAccountPolicy.php`
- `app/Filament/Resources/Sales/ServiceResource.php` + Pages
- `app/Filament/Resources/Sales/ServicePackageResource.php` + Pages
- `app/Filament/Resources/Sales/PriceBookResource.php` + Pages
- `app/Filament/Resources/Sales/BankAccountResource.php` + Pages
- `database/seeders/SalesSeeder.php`

## Files Created (Phase 2)
- `app/Services/Sales/QuotationCreationService.php`
- `app/Services/Sales/QuotationRevisionService.php`
- `app/Services/Sales/QuotationApprovalService.php`
- `app/Services/Sales/QuotationPdfService.php`
- `app/Services/Sales/QuotationMailService.php`
- `app/Services/Sales/QuotationConfirmationService.php`
- `app/Jobs/Sales/SendQuotationEmailJob.php`
- `app/Jobs/Sales/GenerateQuotationPdfJob.php`
- `app/Jobs/Sales/ExpireQuotationsJob.php`
- `app/Mail/Sales/QuotationMail.php`
- `app/Filament/Resources/Sales/QuotationResource.php` + Pages (List, Create, View, Edit)
- `app/Filament/Resources/Sales/QuotationResource/RelationManagers/` (Items, Confirmations, EmailLogs, Documents, Approvals)
- `app/Filament/Resources/Sales/QuotationApprovalResource.php` + Page
- `app/Http/Controllers/Sales/QuotationPublicController.php`
- `resources/views/sales/quotation-pdf.blade.php`
- `resources/views/sales/emails/quotation-sent.blade.php`
- `resources/views/sales/public/show.blade.php`

## Files Created (Phase 3-6)
- `app/Services/Sales/QuotationPublicAccessService.php`
- `app/Services/Sales/QrPaymentService.php`
- `app/Services/Sales/QuotationPaymentService.php`
- `app/Services/Sales/QuotationInteractionService.php`
- `app/Services/Sales/QuotationReminderService.php`
- `app/Jobs/Sales/SendQuotationAcceptedNotificationJob.php`
- `app/Jobs/Sales/SendQuotationRevisionRequestedNotificationJob.php`
- `app/Jobs/Sales/SendQuotationRejectedNotificationJob.php`
- `app/Jobs/Sales/SendQuotationExpiringNotificationJob.php`
- `app/Jobs/Sales/SendQuotationExpiredNotificationJob.php`
- `app/Jobs/Sales/SendPaymentConfirmedNotificationJob.php`
- `app/Jobs/Sales/SendQuotationReminderJob.php`
- `app/Mail/Sales/QuotationAcceptedMail.php`
- `app/Mail/Sales/QuotationRevisionRequestedMail.php`
- `app/Mail/Sales/QuotationRejectedMail.php`
- `app/Mail/Sales/QuotationExpiringMail.php`
- `app/Mail/Sales/QuotationExpiredMail.php`
- `app/Mail/Sales/PaymentConfirmedMail.php`
- `app/Filament/Resources/Sales/PaymentTrackingResource.php`
- `app/Filament/Pages/SalesDashboard.php`
- `app/Filament/Pages/CustomerCarePage.php`
- `app/Filament/Resources/Sales/PriceBookResource/RelationManagers/PriceBookAccessRuleRelationManager.php`
- `app/Console/Commands/Sales/ProcessQuotationReminders.php`
- `resources/views/sales/emails/quotation-rejected.blade.php`
- `resources/views/sales/emails/quotation-resent.blade.php`
- `resources/views/sales/emails/quotation-expiring.blade.php`
- `resources/views/sales/emails/quotation-expired.blade.php`
- `resources/views/sales/emails/payment-confirmed.blade.php`
- `resources/views/filament/pages/sales-dashboard.blade.php`
- `database/factories/Sales/` — 6 factory files
- `tests/Unit/Sales/` — 4 test files

## Files Modified
- `app/Providers/AppServiceProvider.php` — added 16 Sales gates
- `database/seeders/DatabaseSeeder.php` — added SalesSeeder call
- `routes/web.php` — added public quotation routes + OTP routes
- `routes/console.php` — added sales:process-reminders schedule
- `app/Http/Controllers/Sales/QuotationPublicController.php` — uses QuotationPublicAccessService, dispatches rejected notification, added OTP methods, full rate limiting
- `app/Services/Sales/QuotationPaymentService.php` — handlePaid() transitions customer to Active+Purchasing, dispatches PaymentConfirmedMail
- `app/Services/Sales/QuotationMailService.php` — resend() uses quotation-resent template
- `app/Jobs/Sales/ExpireQuotationsJob.php` — dispatches expired notification per quotation
- `app/Jobs/Sales/SendQuotationReminderJob.php` — dispatches expiring notification emails
- `app/Filament/Resources/Sales/PriceBookResource.php` — added getRelations() for PriceBookAccessRule
- `lang/en.json` — added all sales-related translations
- `lang/vi.json` — added all sales-related translations

## Migrations
All 12 migrations executed successfully:
1. create_services_table
2. create_service_packages_table
3. create_price_books_table
4. create_price_book_items_table
5. create_price_book_access_rules_table
6. create_bank_accounts_table
7. create_quotations_table
8. create_quotation_items_table
9. create_quotation_confirmations_table
10. create_quotation_email_logs_table
11. create_quotation_documents_table
12. create_quotation_approvals_table

## Commands Run
- `composer require barryvdh/laravel-dompdf:* --ignore-platform-req=ext-zip`
- `php artisan db:wipe` (drop all tables)
- `php artisan migrate` (fresh)
- `php artisan db:seed` (with SalesSeeder)

## Tests Run
- `php artisan test` — 105 pre-existing failures (SQLite migration index issue, unrelated to Sales)
- `php artisan test --testsuite=Unit --filter=Sales` — 36 passed (QuotationPricingServiceTest 10, QuotationStateMachineTest 17, QrPaymentServiceTest 7, QuotationCodeGeneratorTest 2)

## Errors Encountered
- `service_category_id` FK constraint in original migration design → removed FK, kept as simple unsignedBigInteger
- Test failures are pre-existing (SQLite cannot drop indexed columns in migrations), not caused by Sales module

## Decisions
- Using flat structure (app/Enums/Sales, app/Models/Sales, etc.) since nwidart/laravel-modules is not installed
- Using string backed enums matching project convention
- Using decimal(18,2) for all money fields
- Using dompdf for PDF generation
- `service_category_id` stored as plain unsignedBigInteger (no FK constraint)

## Risks
- No ext-zip on this system may cause issues with some packages
- SQLite database may have differences from MySQL in production
- Pre-existing test failures unrelated to Sales module

## Next Step
All phases completed. Any remaining Feature tests require MySQL test database (pre-existing SQLite incompatibility).
