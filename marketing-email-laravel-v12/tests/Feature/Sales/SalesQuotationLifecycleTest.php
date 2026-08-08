<?php

namespace Tests\Feature\Sales;

use App\Enums\Crm\CustomerConsentStatus;
use App\Enums\Crm\CustomerLifecycleStage;
use App\Enums\Crm\CustomerStatus;
use App\Enums\Crm\InteractionStatus;
use App\Enums\Crm\StaffEmploymentStatus;
use App\Enums\Marketing\EmailEventType;
use App\Enums\Sales\ApprovalStatus;
use App\Enums\Sales\AudienceType;
use App\Enums\Sales\ConfirmationType;
use App\Enums\Sales\DiscountType;
use App\Enums\Sales\DocumentType;
use App\Enums\Sales\EmailStatus;
use App\Enums\Sales\PackageStatus;
use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\PriceBookStatus;
use App\Enums\Sales\QuotationEmailStatus;
use App\Enums\Sales\QuotationStatus;
use App\Enums\Sales\ServiceStatus;
use App\Jobs\Sales\ExpireQuotationsJob;
use App\Jobs\Sales\SendQuotationEmailJob;
use App\Mail\Sales\PaymentConfirmedMail;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerAssignment;
use App\Models\Crm\CustomerInteraction;
use App\Models\Crm\Department;
use App\Models\Crm\Staff;
use App\Models\Marketing\Contact;
use App\Models\Marketing\EmailEvent;
use App\Models\Marketing\EmailTemplate;
use App\Models\Marketing\SendingAccount;
use App\Models\Sales\BankAccount;
use App\Models\Sales\Opportunity;
use App\Models\Sales\PriceBook;
use App\Models\Sales\PriceBookAccessRule;
use App\Models\Sales\PriceBookItem;
use App\Models\Sales\Quotation;
use App\Models\Sales\QuotationDocument;
use App\Models\Sales\Service;
use App\Models\Sales\ServicePackage;
use App\Models\User;
use App\Services\Sales\PriceBookAccessService;
use App\Services\Sales\PriceBookResolverService;
use App\Services\Sales\QrPaymentService;
use App\Services\Sales\QuotationApprovalService;
use App\Services\Sales\QuotationCodeGenerator;
use App\Services\Sales\QuotationConfirmationService;
use App\Services\Sales\QuotationCreationService;
use App\Services\Sales\QuotationMailService;
use App\Services\Sales\QuotationPaymentService;
use App\Services\Sales\QuotationPdfService;
use App\Services\Sales\QuotationPublicAccessService;
use App\Services\Sales\QuotationReminderService;
use App\Services\Sales\QuotationRevisionService;
use App\Services\Sales\QuotationTemplateRenderer;
use App\Services\Sales\ServiceCatalogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Covers the whole Sales module: catalog/price book setup, quotation creation,
 * approval, email sending + CRM sync, public confirmation (view/accept/reject/
 * request revision), payment lifecycle, expiry/reminders, revisions, soft
 * deletes and PDF/QR generation.
 */
class SalesQuotationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $staffUser;

    private User $financeUser;

    private Staff $staff;

    private PriceBook $priceBook;

    private PriceBookItem $item;

    private BankAccount $bankAccount;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'secret', 'role' => 'admin',
        ]);
        $this->manager = User::query()->create([
            'name' => 'Sales Manager', 'email' => 'sales-manager@example.test', 'password' => 'secret', 'role' => 'sales_manager',
        ]);
        $this->staffUser = User::query()->create([
            'name' => 'Sales Staff', 'email' => 'staff@example.test', 'password' => 'secret', 'role' => 'sales_staff',
        ]);
        $this->financeUser = User::query()->create([
            'name' => 'Finance', 'email' => 'finance@example.test', 'password' => 'secret', 'role' => 'finance_staff',
        ]);
        $this->staff = Staff::query()->create([
            'user_id' => $this->staffUser->id,
            'employee_code' => 'NV001',
            'full_name' => 'Staff',
            'department_id' => Department::query()->firstOrCreate(
                ['code' => 'sales'],
                ['name' => 'Kinh doanh', 'function_key' => 'sales', 'sort_order' => 4, 'is_active' => true]
            )->id,
            'employment_status' => StaffEmploymentStatus::Active,
            'can_receive_customers' => true,
        ]);

        SendingAccount::query()->create([
            'name' => 'Sales SMTP',
            'provider' => 'smtp',
            'from_name' => 'DTH Sales',
            'from_email' => 'sales@example.test',
            'reply_to' => 'sales@example.test',
            'config_encrypted' => [
                'host' => 'smtp.example.test',
                'port' => 587,
                'username' => 'sales@example.test',
                'password' => 'secret',
                'encryption' => 'tls',
            ],
            'daily_limit' => 1000,
            'hourly_limit' => 100,
            'status' => 'active',
            'department_id' => $this->staff->department_id,
        ]);

        $this->makeCatalog();
        $this->bankAccount = BankAccount::query()->create([
            'bank_code' => 'VCB',
            'bank_name' => 'Vietcombank',
            'account_number' => '1032666491',
            'account_name' => 'CONG TY TNHH TEST',
            'status' => 'active',
            'is_default' => true,
        ]);
        $this->customer = $this->makeCustomer();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    private function makeCatalog(): void
    {
        $service = Service::query()->create([
            'service_code' => 'SV-WEB',
            'name' => 'Thiết kế website',
            'slug' => 'thiet-ke-website',
            'status' => ServiceStatus::Active,
        ]);
        $package = ServicePackage::query()->create([
            'service_id' => $service->id,
            'package_code' => 'PK-WEB-BASIC',
            'name' => 'Gói website cơ bản',
            'audience_type' => AudienceType::Both,
            'unit' => 'tháng',
            'default_quantity' => 1,
            'status' => PackageStatus::Active,
        ]);
        $this->priceBook = PriceBook::query()->create([
            'price_book_code' => 'PBK-2026',
            'name' => 'Bảng giá 2026',
            'audience_type' => AudienceType::Both,
            'currency' => 'VND',
            'valid_from' => now()->subDay()->toDateString(),
            'valid_until' => now()->addYear()->toDateString(),
            'status' => PriceBookStatus::Active,
            'is_default' => true,
        ]);
        $this->item = PriceBookItem::query()->create([
            'price_book_id' => $this->priceBook->id,
            'service_package_id' => $package->id,
            'unit_price' => 1_000_000,
            'vat_rate' => 10,
            'sort_order' => 1,
        ]);
    }

    private function makeCustomer(array $overrides = []): Customer
    {
        $contact = Contact::query()->create(['contact_type' => 'personal']);

        return Customer::query()->create(array_merge([
            'customer_code' => 'CUS-'.strtoupper(Str::random(8)),
            'contact_id' => $contact->id,
            'customer_type' => 'personal',
            'first_name' => 'Nguyễn',
            'last_name' => 'Văn A',
            'email' => 'a'.Str::random(4).'@example.test',
            'phone' => '0912345678',
            'consent_status' => CustomerConsentStatus::Subscribed,
            'status' => CustomerStatus::Potential,
        ], $overrides));
    }

    private function assignCustomerToStaff(): void
    {
        CustomerAssignment::query()->create([
            'customer_id' => $this->customer->id,
            'staff_id' => $this->staff->id,
            'assignment_type' => 'owner',
            'status' => 'active',
            'reason' => 'manual',
            'starts_at' => now(),
            'assigned_by_user_id' => $this->admin->id,
        ]);
    }

    private function grantStaffBookAccess(): void
    {
        PriceBookAccessRule::query()->create([
            'price_book_id' => $this->priceBook->id,
            'access_type' => 'department',
            'department' => 'sales',
            'can_view' => true,
            'can_create_quotation' => true,
        ]);
    }

    private function itemLine(array $overrides = []): array
    {
        return array_merge([
            'price_book_item_id' => $this->item->id,
            'quantity' => 1,
            'unit_price' => 1_000_000,
        ], $overrides);
    }

    private function createQuotation(User $user, array $items, array $params = [], ?Customer $customer = null): Quotation
    {
        $params = array_merge([
            'bank_account_id' => $this->bankAccount->id,
        ], $params);

        return app(QuotationCreationService::class)->create(
            $customer ?? $this->customer,
            $user,
            $this->priceBook,
            $items,
            $params,
        );
    }

    private function createOpportunityQuotation(
        Opportunity $opportunity,
        User $user,
    ): Quotation {
        return app(QuotationCreationService::class)
            ->createForOpportunity(
                $opportunity,
                $user,
                $this->priceBook,
                [$this->itemLine()],
            );
    }

    private function createSentQuotation(array $params = []): Quotation
    {
        $quotation = $this->createQuotation($this->admin, [$this->itemLine()], $params);
        $quotation->update(['status' => QuotationStatus::Sent->value, 'sent_at' => now()]);

        return $quotation;
    }

    private function verifyOtpForQuotation(Quotation $quotation): string
    {
        $access = app(QuotationPublicAccessService::class);
        $email = $quotation->party_email;
        $key = $access->otpKey($quotation, $email);
        $access->storeOtp($key, '123456');
        $this->assertTrue($access->verifyOtp($key, '123456'));

        return $email;
    }

    // ─── 1. Catalog & service resolution ──────────────────────────────────

    public function test_catalog_returns_active_services_with_packages(): void
    {
        $catalog = app(ServiceCatalogService::class)->getActiveServices();

        $this->assertCount(1, $catalog);
        $this->assertCount(1, $catalog->first()->packages);
        $this->assertSame('PK-WEB-BASIC', $catalog->first()->packages->first()->package_code);
        $this->assertSame($this->item->id, app(ServiceCatalogService::class)->findPackageByCode('PK-WEB-BASIC')->id);
        $this->assertNull(app(ServiceCatalogService::class)->findPackageByCode('DOES-NOT-EXIST'));
    }

    public function test_admin_resolves_price_book_and_items(): void
    {
        $resolver = app(PriceBookResolverService::class);

        $found = $resolver->resolvePriceBook($this->admin, $this->priceBook->id);
        $this->assertSame($this->priceBook->id, $found->id);
        $this->assertCount(1, $resolver->getItemsForPriceBook($found));
        $this->assertNotNull($resolver->resolveItem($found, $this->item->id));
        $this->assertNull($resolver->resolveItem($found, 999999));
    }

    // ─── 2. Quotation creation & totals ───────────────────────────────────

    public function test_admin_creates_quotation_with_correct_totals_and_snapshots(): void
    {
        $quotation = $this->createQuotation($this->admin, [
            $this->itemLine(),
        ], ['title' => 'Cung cấp website']);

        $this->assertSame(QuotationStatus::Draft, $quotation->status);
        $this->assertSame(1, $quotation->version);
        $this->assertSame('Cung cấp website', $quotation->title);
        $this->assertMatchesRegularExpression('/^QT\\d{4}\\d{5}$/', $quotation->quotation_code);
        $this->assertSame(64, strlen($quotation->public_token));
        $customerSnapshot = (array) $quotation->customer_snapshot;
        $paymentSnapshot = (array) $quotation->payment_snapshot;
        $this->assertSame($this->customer->id, $customerSnapshot['id']);
        $this->assertSame(PaymentStatus::Unpaid, $quotation->payment_status);
        $this->assertSame(EmailStatus::Unsent, $quotation->email_status);
        $this->assertSame((int) $this->bankAccount->id, (int) $paymentSnapshot['bank_account_id']);

        $this->assertSame('1000000.00', $quotation->subtotal);
        $this->assertSame('0.00', $quotation->discount_total);
        $this->assertSame('100000.00', $quotation->tax_total);
        $this->assertSame('1100000.00', $quotation->grand_total);

        $this->assertCount(1, $quotation->items);
        $item = $quotation->items->first();
        $this->assertSame('SV-WEB', $item->service_code_snapshot);
        $this->assertSame('PK-WEB-BASIC', $item->package_code_snapshot);
        $this->assertSame(1_000_000, (int) $item->unit_price);
        $this->assertSame('1100000.00', $item->line_total);
    }

    public function test_discount_and_vat_are_calculated_on_totals(): void
    {
        $quotation = $this->createQuotation($this->admin, [
            $this->itemLine([
                'quantity' => 2,
                'discount_type' => DiscountType::Percentage->value,
                'discount_value' => 10,
                'vat_rate' => 10,
            ]),
        ]);

        $this->assertSame('2000000.00', $quotation->subtotal);
        $this->assertSame('200000.00', $quotation->discount_total);
        $this->assertSame('180000.00', $quotation->tax_total);
        $this->assertSame('1980000.00', $quotation->grand_total);
    }

    public function test_archived_customer_cannot_receive_quotation(): void
    {
        $archived = $this->makeCustomer(['status' => CustomerStatus::Archived->value]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->createQuotation($this->admin, [$this->itemLine()], [], $archived);
    }

    public function test_discount_above_item_limit_is_rejected(): void
    {
        $this->item->update(['maximum_discount_value' => 100]);
        $this->item->refresh();

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->createQuotation($this->admin, [
            $this->itemLine([
                'discount_type' => DiscountType::Percentage->value,
                'discount_value' => 20, // 20% of 1.000.000 = 200.000 > 100.000 cap
            ]),
        ]);
    }

    // ─── 3. Access / RBAC ────────────────────────────────────────────────

    public function test_unassigned_staff_cannot_create_quotation(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->createQuotation($this->staffUser, [$this->itemLine()]);
    }

    public function test_assigned_staff_without_price_book_rule_cannot_create_quotation(): void
    {
        $this->assignCustomerToStaff();

        $this->expectException(\RuntimeException::class);
        $this->createQuotation($this->staffUser, [$this->itemLine()]);
    }

    public function test_assigned_staff_with_access_rule_can_create_quotation(): void
    {
        $this->assignCustomerToStaff();
        $this->grantStaffBookAccess();

        $this->assertTrue(app(PriceBookAccessService::class)->canCreateQuotation($this->staffUser, $this->priceBook));

        $quotation = $this->createQuotation($this->staffUser, [$this->itemLine()]);
        $this->assertSame($this->staff->id, $quotation->assigned_staff_id);
        $this->assertSame(QuotationStatus::Draft, $quotation->status);
    }

    // ─── 4. Approval workflow ─────────────────────────────────────────────

    public function test_submit_and_approve_flow(): void
    {
        $quotation = $this->createQuotation($this->admin, [$this->itemLine()]);
        $approvalSvc = app(QuotationApprovalService::class);

        $quotation = $approvalSvc->submitForApproval($quotation, $this->manager);
        $this->assertSame(QuotationStatus::PendingApproval, $quotation->status);
        $this->assertTrue($quotation->approvals->contains(fn ($a) => $a->status === ApprovalStatus::Pending));

        $quotation = $approvalSvc->approve($quotation, $this->manager, 'OK');
        $this->assertSame(QuotationStatus::Approved, $quotation->status);
        $this->assertSame($this->manager->id, $quotation->approved_by);
        $this->assertNotNull($quotation->approved_at);
        $this->assertSame(ApprovalStatus::Approved, $quotation->approvals->first()->status);
        $this->assertSame($this->manager->id, $quotation->approvals->first()->approver_user_id);
    }

    public function test_rejection_returns_quotation_to_draft(): void
    {
        $quotation = $this->createQuotation($this->admin, [$this->itemLine()]);
        $approvalSvc = app(QuotationApprovalService::class);

        $approvalSvc->submitForApproval($quotation, $this->manager);
        $quotation = $approvalSvc->reject($quotation, $this->manager, 'Giá cao quá');

        $this->assertSame(QuotationStatus::Draft, $quotation->status);
        $this->assertSame(ApprovalStatus::Rejected, $quotation->approvals->first()->status);
        $this->assertSame('Giá cao quá', $quotation->approvals->first()->reason);
    }

    public function test_cancellation_logs_an_approval_record(): void
    {
        $quotation = $this->createQuotation($this->admin, [$this->itemLine()]);
        $approvalSvc = app(QuotationApprovalService::class);

        $approvalSvc->submitForApproval($quotation, $this->manager);
        $quotation = $approvalSvc->logCancellation($quotation, $this->manager, 'Khách không mua');

        $this->assertSame(QuotationStatus::Cancelled, $quotation->status);
        $this->assertNotNull($quotation->cancelled_at);
        $this->assertTrue($quotation->approvals()->where('status', ApprovalStatus::Cancelled->value)->exists());
    }

    // ─── 5. Emailing + CRM sync ───────────────────────────────────────────

    public function test_sending_queues_email_job_and_syncs_to_crm_when_run(): void
    {
        Mail::fake();
        Queue::fake();

        $this->assignCustomerToStaff();
        $this->grantStaffBookAccess();

        $quotation = $this->createQuotation($this->staffUser, [$this->itemLine()]);
        $quotation = app(QuotationApprovalService::class)->approve(
            app(QuotationApprovalService::class)->submitForApproval($quotation, $this->staffUser),
            $this->manager,
        );

        $log = app(QuotationMailService::class)->send(
            $quotation,
            $this->staffUser,
            $quotation->party_email,
        );

        $this->assertSame(QuotationEmailStatus::Queued, $log->status);
        $this->assertSame(EmailStatus::Queued, $quotation->refresh()->email_status);
        Queue::assertPushed(SendQuotationEmailJob::class);

        $pdfDoc = $quotation->documents()->latest('id')->firstOrFail();
        (new SendQuotationEmailJob($log, $pdfDoc))->handle(
            app(\App\Services\Sales\QuotationSendingAccountService::class),
            app(\App\Services\Sales\QuotationStateMachine::class),
            app(\App\Services\Sales\QuotationInteractionService::class),
        );

        $log->refresh();
        $this->assertSame(QuotationEmailStatus::Sent, $log->status);
        $this->assertSame(EmailStatus::Sent, $log->quotation->refresh()->email_status);

        $event = EmailEvent::where('customer_id', $this->customer->id)
            ->where('event_type', EmailEventType::Sent->value)
            ->latest('id')->first();
        $this->assertNotNull($event);
        $this->assertSame('quotation', $event->event_payload['context'] ?? null);

        $interaction = CustomerInteraction::where('customer_id', $this->customer->id)
            ->where('interaction_type', 'quotation_sent')->latest('id')->first();
        $this->assertNotNull($interaction);
    }

    public function test_send_rejects_quotation_that_cannot_be_sent(): void
    {
        $quotation = $this->createQuotation($this->admin, [$this->itemLine()]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(QuotationMailService::class)->send($quotation, $this->admin, $quotation->party_email);
    }

    public function test_template_renderer_replaces_tokens(): void
    {
        $quotation = $this->createQuotation($this->admin, [$this->itemLine()]);

        $template = EmailTemplate::query()->create([
            'name' => 'Quotation',
            'category' => 'quotation',
            'subject' => 'Báo giá {{quotation_code}}',
            'html_body' => '<p>Chào {{customer_name}}</p>',
            'status' => 'active',
        ]);

        $rendered = app(QuotationTemplateRenderer::class)->render($template, $quotation);
        $this->assertStringContainsString($quotation->quotation_code, $rendered['subject']);
        $this->assertStringContainsString($this->customer->display_name, $rendered['body']);
    }

    // ─── 6. Public customer workflows (view / accept / reject / revise) ───

    public function test_public_view_marks_quotation_as_viewed(): void
    {
        $quotation = $this->createSentQuotation();

        $access = app(QuotationPublicAccessService::class);

        $found = $access->findQuotation($quotation->quotation_code, $quotation->public_token);
        $this->assertInstanceOf(Quotation::class, $found);
        $this->assertNull($access->findQuotation($quotation->quotation_code, 'bogus'));

        $access->trackView($found);
        $this->assertSame(QuotationStatus::Viewed, $found->fresh()->status);
        $this->assertNotNull($found->fresh()->first_viewed_at);
    }

    public function test_customer_accepts_quotation_through_public_route(): void
    {
        Mail::fake();

        $quotation = $this->createSentQuotation();

        $email = $this->verifyOtpForQuotation($quotation);

        $response = $this->post(route('sales.quotation.public.accept', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]), [
            'signer_name' => 'Nguyễn Văn A',
            'signer_email' => $email,
            'otp_email' => $email,
        ]);

        $response->assertRedirect();

        $quote = $quotation->fresh();
        $this->assertSame(QuotationStatus::Accepted, $quote->status);
        $this->assertNotNull($quote->accepted_at);

        $confirmation = $quote->confirmations->first();
        $this->assertSame(ConfirmationType::Accepted, $confirmation->confirmation_type);

        $interaction = CustomerInteraction::where('customer_id', $this->customer->id)
            ->where('interaction_type', 'quotation_accepted')->first();
        $this->assertNotNull($interaction);
    }

    public function test_customer_rejects_quotation(): void
    {
        $quotation = $this->createSentQuotation();

        $email = $this->verifyOtpForQuotation($quotation);

        $this->post(route('sales.quotation.public.reject', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]), [
            'signer_name' => 'Nguyễn Văn A',
            'signer_email' => $email,
            'otp_email' => $email,
            'reason' => 'Không phù hợp',
        ]);

        $this->assertSame(QuotationStatus::Rejected, $quotation->fresh()->status);
    }

    public function test_revision_request_then_create_revision_supersedes_original(): void
    {
        $quotation = $this->createSentQuotation();

        $email = $quotation->party_email;
        app(QuotationConfirmationService::class)->requestRevision($quotation, [
            'signer_name' => 'a',
            'signer_email' => $email,
            'reason' => 'Giá tăng lên',
        ], $email);
        $this->assertSame(QuotationStatus::RevisionRequested, $quotation->fresh()->status);

        $revision = app(QuotationRevisionService::class)->createRevision(
            $quotation,
            $this->manager,
            [],
            ['title' => 'Báo giá sửa đổi']
        );

        $this->assertSame(2, $revision->version);
        $this->assertNotSame($quotation->quotation_code, $revision->quotation_code);
        $this->assertSame($quotation->id, $revision->replaces_quotation_id);
        $this->assertSame(QuotationStatus::Draft, $revision->status);
        $this->assertSame(QuotationStatus::Superseded, $quotation->fresh()->status);
    }

    // ─── 7. Payment lifecycle & customer upgrade ─────────────────────────

    public function test_paying_quotation_upgrades_customer_and_records_email(): void
    {
        Mail::fake();

        $quotation = $this->createQuotation($this->admin, [$this->itemLine()]);
        $quotation->update(['status' => QuotationStatus::Accepted->value, 'accepted_at' => now()]);

        app(QuotationPaymentService::class)->updateStatus(
            $quotation,
            PaymentStatus::Paid,
            $this->financeUser,
            'Chuyển khoản thành công'
        );

        $quoted = $quotation->fresh();
        $this->assertSame(PaymentStatus::Paid, $quoted->payment_status);

        $customer = $this->customer->fresh();
        $this->assertSame(CustomerStatus::Active, $customer->status);
        $this->assertSame(CustomerLifecycleStage::Purchasing->value, $customer->lifecycle_stage);
        $this->assertNotNull($customer->first_purchase_at);

        fwrite(STDERR, "\n\nSENT COUNT: ".count(Mail::sent(PaymentConfirmedMail::class))."\n");

        Mail::assertSent(PaymentConfirmedMail::class);

        $event = EmailEvent::where('customer_id', $this->customer->id)
            ->where('event_type', EmailEventType::Sent->value)
            ->latest('id')->first();
        $this->assertSame('payment_confirmed', $event->event_payload['context']);

        $interaction = CustomerInteraction::where('customer_id', $this->customer->id)
            ->where('interaction_type', 'payment_updated')->first();
        $this->assertNotNull($interaction);
    }

    public function test_invalid_payment_transition_is_rejected(): void
    {
        $quotation = $this->createQuotation($this->admin, [$this->itemLine()]);
        $quotation->update(['payment_status' => PaymentStatus::Paid->value]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(QuotationPaymentService::class)->updateStatus($quotation, PaymentStatus::Unpaid, $this->financeUser);
    }

    // ─── 8. Expiry & reminders ───────────────────────────────────────────

    public function test_expire_quotations_job_expires_only_open_statuses(): void
    {
        $approved = $this->createQuotation($this->admin, [$this->itemLine()]);
        $approved->update(['status' => QuotationStatus::Approved->value, 'valid_until' => now()->subDay()]);

        $draft = $this->createQuotation($this->admin, [$this->itemLine()]);
        $draft->update(['valid_until' => now()->subDay()]);

        (new ExpireQuotationsJob)->handle();

        $this->assertSame(QuotationStatus::Expired, $approved->fresh()->status);
        $this->assertNotNull($approved->fresh()->expired_at);
        $this->assertSame(QuotationStatus::Draft, $draft->fresh()->status);
    }

    public function test_reminder_service_finds_follow_ups(): void
    {
        $reminder = app(QuotationReminderService::class);

        $sentNotViewed = $this->createSentQuotation();
        $sentNotViewed->update(['sent_at' => now()->subDays(3)]);

        $viewed = $this->createQuotation($this->admin, [$this->itemLine()]);
        $viewed->update(['status' => QuotationStatus::Viewed->value, 'last_viewed_at' => now()->subDays(5)]);

        $expiring = $this->createQuotation($this->admin, [$this->itemLine()]);
        $expiring->update(['status' => QuotationStatus::Approved->value, 'valid_until' => now()->addDays(2)]);

        $this->assertTrue($reminder->getSentNotViewed(2)->contains('id', $sentNotViewed->id));
        $this->assertTrue($reminder->getViewedNotResponded(3)->contains('id', $viewed->id));
        $this->assertTrue($reminder->getExpiringSoon(2)->contains('id', $expiring->id));
    }

    public function test_follow_up_task_is_created(): void
    {
        $quotation = $this->createQuotation($this->admin, [$this->itemLine()]);

        $task = app(QuotationReminderService::class)->createFollowUpTask($quotation, 'Chăm sóc lại');
        $this->assertSame('quotation_follow_up', $task->interaction_type);
        $this->assertSame(InteractionStatus::Completed, $task->status);
    }

    // ─── 9. PDF & QR ────────────────────────────────────────────────────

    public function test_quotation_pdf_is_generated_and_stored(): void
    {
        Pdf::swap(new class
        {
            public function loadHTML(string $html): self
            {
                return $this;
            }

            public function setPaper(mixed ...$args): self
            {
                return $this;
            }

            public function setOptions(array $options): self
            {
                return $this;
            }

            public function save(string $path): self
            {
                File::put($path, '%PDF-1.4 fake');

                return $this;
            }
        });

        $quotation = $this->createQuotation($this->admin, [$this->itemLine()]);

        $doc = app(QuotationPdfService::class)->generate($quotation);

        $this->assertSame(DocumentType::Pdf, $doc->document_type);
        $this->assertSame('application/pdf', $doc->mime_type);
        $this->assertTrue(Storage::disk('local')->exists($doc->file_path));
        $this->assertSame($doc->id, app(QuotationPdfService::class)->getLatestPdf($quotation)->id);
    }

    public function test_qr_html_falls_back_to_image_endpoint_without_bin(): void
    {
        $html = app(QrPaymentService::class)->generateHtml('VCB', '1234567890', null, null, null, 150);

        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('width="150"', $html);
        $this->assertStringContainsString('img.vietqr.io', $html);
    }

    // ─── 10. Soft delete ─────────────────────────────────────────────────

    public function test_quotation_is_soft_deleted_and_can_be_restored(): void
    {
        $quotation = $this->createQuotation($this->admin, [$this->itemLine()]);

        $quotation->delete();
        $this->assertNotNull($quotation->trashed());
        $this->assertNull(Quotation::whereKey($quotation->id)->first());
        $this->assertNotNull(Quotation::withTrashed()->whereKey($quotation->id)->first());

        $quotation->restore();
        $this->assertNotNull(Quotation::find($quotation->id));
    }

    // ─── 11. Code generator ─────────────────────────────────────────────

    public function test_code_generator_sequences_codes_by_year(): void
    {
        $generator = app(QuotationCodeGenerator::class);
        $year = now()->format('Y');

        $this->createQuotation($this->admin, [$this->itemLine()]);
        $this->assertSame("QT{$year}00001", $this->customer->quotations()->latest('id')->value('quotation_code'));
        $this->assertSame("QT{$year}00002", $generator->generate());
    }
}
