<?php

namespace Tests\Feature\Sales;

use App\Enums\Crm\StaffEmploymentStatus;
use App\Enums\Sales\EmailStatus;
use App\Enums\Sales\OpportunityStage;
use App\Enums\Sales\PackageStatus;
use App\Enums\Sales\PriceBookStatus;
use App\Enums\Sales\QuotationEmailStatus;
use App\Enums\Sales\ServiceStatus;
use App\Jobs\Sales\SendQuotationEmailJob;
use App\Models\Crm\Department;
use App\Models\Crm\Staff;
use App\Models\Marketing\EmailEvent;
use App\Models\Marketing\SendingAccount;
use App\Models\Sales\BankAccount;
use App\Models\Sales\Opportunity;
use App\Models\Sales\PriceBook;
use App\Models\Sales\PriceBookAccessRule;
use App\Models\Sales\PriceBookItem;
use App\Models\Sales\Quotation;
use App\Models\Sales\Service;
use App\Models\Sales\ServicePackage;
use App\Models\User;
use App\Services\Sales\QuotationApprovalService;
use App\Services\Sales\QuotationCreationService;
use App\Services\Sales\QuotationMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\Support\MakesV1Actors;
use Tests\TestCase;

class QuotationMailWithoutCustomerTest extends TestCase
{
    use MakesV1Actors;
    use RefreshDatabase;

    private User $manager;

    private User $staffUser;

    private Staff $staff;

    private PriceBook $priceBook;

    private PriceBookItem $item;

    private Opportunity $opportunity;

    private BankAccount $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('v1_workflow.quotation_approval.mode', 'always');

        [$this->manager] = $this->makeV1SalesManager('Mail Sales Manager');
        [$this->staffUser, $this->staff] = $this->makeV1SalesStaff('Mail Sales Staff');
        $salesDepartment = $this->staff->department;

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
            'department_id' => $salesDepartment->id,
        ]);

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
            'audience_type' => 'both',
            'unit' => 'gói',
            'default_quantity' => 1,
            'status' => PackageStatus::Active,
        ]);

        $this->priceBook = PriceBook::query()->create([
            'price_book_code' => 'PBK-MAIL-'.fake()->unique()->numberBetween(1, 999999),
            'name' => 'Bảng giá mail',
            'audience_type' => 'both',
            'currency' => 'VND',
            'valid_from' => now()->subDay()->toDateString(),
            'valid_until' => now()->addYear()->toDateString(),
            'status' => PriceBookStatus::Active,
        ]);

        $this->item = PriceBookItem::query()->create([
            'price_book_id' => $this->priceBook->id,
            'service_package_id' => $package->id,
            'unit_price' => 1_000_000,
            'vat_rate' => 10,
            'sort_order' => 1,
        ]);

        PriceBookAccessRule::query()->create([
            'price_book_id' => $this->priceBook->id,
            'access_type' => 'department',
            'department' => $this->staff->department->code,
            'can_view' => true,
            'can_create_quotation' => true,
        ]);

        $this->bankAccount = BankAccount::query()->create([
            'bank_code' => 'VCB',
            'bank_name' => 'Vietcombank',
            'account_number' => '1032666491',
            'account_name' => 'CONG TY TNHH TEST',
            'status' => 'active',
            'is_default' => true,
        ]);

        $this->opportunity = Opportunity::factory()
            ->qualified()
            ->assignedTo($this->staff)
            ->create([
                'service_interest' => 'PK-WEB-BASIC',
            ]);
    }

    private function makeApprovedQuotation(): Quotation
    {
        $quotation = app(QuotationCreationService::class)->createForOpportunity(
            $this->opportunity,
            $this->staffUser,
            $this->priceBook,
            [[
                'price_book_item_id' => $this->item->id,
                'quantity' => 1,
            ]],
            [
                'bank_account_id' => $this->bankAccount->id,
            ],
        );

        $quotation = app(QuotationApprovalService::class)->submitForApproval(
            $quotation,
            $this->staffUser,
        );

        return app(QuotationApprovalService::class)->approve(
            $quotation,
            $this->manager,
        );
    }

    public function test_recipient_defaults_to_party_email(): void
    {
        Mail::fake();
        Queue::fake();

        $quotation = $this->makeApprovedQuotation();
        $this->assertNotNull($quotation->party_email);
        $this->assertNull($quotation->customer_id);

        $log = app(QuotationMailService::class)->send(
            $quotation,
            $this->staffUser,
            $quotation->party_email,
        );

        $this->assertSame($quotation->party_email, $log->recipient_email);
        Queue::assertPushed(SendQuotationEmailJob::class);
    }

    public function test_email_job_runs_without_customer_and_syncs_crm(): void
    {
        Mail::fake();
        Queue::fake();

        $quotation = $this->makeApprovedQuotation();

        $log = app(QuotationMailService::class)->send(
            $quotation,
            $this->staffUser,
            $quotation->party_email,
        );

        Queue::assertPushed(SendQuotationEmailJob::class);

        $pdfDoc = $quotation->documents()->latest('id')->firstOrFail();
        (new SendQuotationEmailJob($log, $pdfDoc))->handle(
            app(\App\Services\Marketing\SendingAccountMailerService::class),
        );

        $this->assertSame(QuotationEmailStatus::Sent, $log->fresh()->status);
        $this->assertSame(EmailStatus::Sent, $quotation->fresh()->email_status);

        $this->assertDatabaseHas('opportunity_interactions', [
            'opportunity_id' => $this->opportunity->id,
            'interaction_type' => 'quotation_sent',
        ]);

        $event = EmailEvent::where('contact_id', $quotation->contact_id)
            ->latest('id')->first();
        $this->assertNotNull($event);
        $this->assertSame('quotation', $event->event_payload['context'] ?? null);

        $this->assertSame(
            OpportunityStage::Proposal,
            $this->opportunity->fresh()->stage
        );
    }
}
