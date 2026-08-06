<?php

namespace Tests\Feature\Sales;

use App\Enums\Crm\StaffEmploymentStatus;
use App\Enums\Sales\DocumentType;
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
use App\Models\Sales\Opportunity;
use App\Models\Sales\PriceBook;
use App\Models\Sales\PriceBookItem;
use App\Models\Sales\Quotation;
use App\Models\Sales\QuotationDocument;
use App\Models\Sales\Service;
use App\Models\Sales\ServicePackage;
use App\Models\User;
use App\Services\Sales\QuotationApprovalService;
use App\Services\Sales\QuotationCreationService;
use App\Services\Sales\QuotationMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QuotationMailWithoutCustomerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private PriceBook $priceBook;

    private PriceBookItem $item;

    private Opportunity $opportunity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-'.fake()->unique()->numberBetween(1, 999999).'@example.test',
            'password' => 'secret',
            'role' => 'admin',
        ]);

        $this->manager = User::query()->create([
            'name' => 'CSM',
            'email' => 'csm-'.fake()->unique()->numberBetween(1, 999999).'@example.test',
            'password' => 'secret',
            'role' => 'customer_service_manager',
        ]);

        $staffUser = User::query()->create([
            'name' => 'Staff',
            'email' => 'staff-'.fake()->unique()->numberBetween(1, 999999).'@example.test',
            'password' => 'secret',
            'role' => 'customer_service_staff',
        ]);

        $staff = Staff::query()->create([
            'user_id' => $staffUser->id,
            'employee_code' => 'NV'.fake()->unique()->numberBetween(1000, 999999),
            'full_name' => 'Staff',
            'department_id' => Department::query()->firstOrCreate(
                ['code' => 'sales'],
                ['name' => 'Kinh doanh', 'sort_order' => 4, 'is_active' => true]
            )->id,
            'employment_status' => StaffEmploymentStatus::Active,
            'can_receive_customers' => true,
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
            'unit' => 'tháng',
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

        $this->opportunity = Opportunity::factory()
            ->qualified()
            ->assignedTo($staff)
            ->create();
    }

    private function makeApprovedQuotation(): Quotation
    {
        $quotation = app(QuotationCreationService::class)->createForOpportunity(
            $this->opportunity,
            $this->admin,
            $this->priceBook,
            [[
                'price_book_item_id' => $this->item->id,
                'quantity' => 1,
                'unit_price' => 1_000_000,
            ]],
        );

        return app(QuotationApprovalService::class)->approve(
            app(QuotationApprovalService::class)->submitForApproval(
                $quotation,
                $this->manager,
            ),
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
            $this->admin,
            $quotation->party_email,
        );

        $this->assertSame($quotation->party_email, $log->recipient_email);
    }

    public function test_email_job_runs_without_customer_and_syncs_crm(): void
    {
        Mail::fake();
        Queue::fake();

        $quotation = $this->makeApprovedQuotation();

        QuotationDocument::query()->create([
            'quotation_id' => $quotation->id,
            'version' => 1,
            'document_type' => DocumentType::Pdf,
            'file_path' => 'quotations/'.$quotation->quotation_code.'/fake.pdf',
            'file_name' => 'fake.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 123,
            'file_hash' => 'abc',
            'generated_at' => now(),
        ]);

        $log = app(QuotationMailService::class)->send(
            $quotation,
            $this->admin,
            $quotation->party_email,
        );

        Queue::assertPushed(SendQuotationEmailJob::class);

        (new SendQuotationEmailJob($log, $log->quotation->documents()->latest('id')->first()))->handle();

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
