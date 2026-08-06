<?php

namespace Tests\Feature\Sales;

use App\Enums\Crm\StaffEmploymentStatus;
use App\Enums\Sales\DocumentType;
use App\Enums\Sales\PackageStatus;
use App\Enums\Sales\PriceBookStatus;
use App\Enums\Sales\ServiceStatus;
use App\Models\Crm\Department;
use App\Models\Crm\Staff;
use App\Models\Sales\Opportunity;
use App\Models\Sales\PriceBook;
use App\Models\Sales\PriceBookItem;
use App\Models\Sales\Quotation;
use App\Models\Sales\QuotationDocument;
use App\Models\Sales\Service;
use App\Models\Sales\ServicePackage;
use App\Models\User;
use App\Services\Sales\QuotationCreationService;
use App\Services\Sales\QuotationPdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuotationPdfWithoutCustomerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Pdf::swap(new class
        {
            public function loadHTML(string $html): self
            {
                $this->html = $html;

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
    }

    private function makeOpportunityQuotation(): Quotation
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-'.fake()->unique()->numberBetween(1, 999999).'@example.test',
            'password' => 'secret',
            'role' => 'admin',
        ]);

        $staff = Staff::query()->create([
            'user_id' => $admin->id,
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

        $priceBook = PriceBook::query()->create([
            'price_book_code' => 'PBK-PDF-'.fake()->unique()->numberBetween(1, 999999),
            'name' => 'Bảng giá pdf',
            'audience_type' => 'both',
            'currency' => 'VND',
            'valid_from' => now()->subDay()->toDateString(),
            'valid_until' => now()->addYear()->toDateString(),
            'status' => PriceBookStatus::Active,
        ]);

        $item = PriceBookItem::query()->create([
            'price_book_id' => $priceBook->id,
            'service_package_id' => $package->id,
            'unit_price' => 1_000_000,
            'vat_rate' => 10,
            'sort_order' => 1,
        ]);

        $opportunity = Opportunity::factory()
            ->qualified()
            ->assignedTo($staff)
            ->create();

        return app(QuotationCreationService::class)->createForOpportunity(
            $opportunity,
            $admin,
            $priceBook,
            [[
                'price_book_item_id' => $item->id,
                'quantity' => 1,
                'unit_price' => 1_000_000,
            ]],
        );
    }

    public function test_pdf_generates_when_customer_is_null(): void
    {
        $quotation = $this->makeOpportunityQuotation();
        $this->assertNull($quotation->customer_id);

        $doc = app(QuotationPdfService::class)->generate($quotation);

        $this->assertInstanceOf(QuotationDocument::class, $doc);
        $this->assertSame(DocumentType::Pdf, $doc->document_type);
        $this->assertSame('application/pdf', $doc->mime_type);
        $this->assertTrue(Storage::disk('local')->exists($doc->file_path));
    }

    public function test_pdf_template_renders_party_name_from_snapshot(): void
    {
        $quotation = $this->makeOpportunityQuotation();

        $html = view('sales.quotation-pdf', ['quotation' => $quotation])->render();

        $this->assertStringContainsString(
            $quotation->party_display_name,
            $html
        );
        $this->assertStringContainsString(
            $quotation->quotation_code,
            $html
        );
        $this->assertStringNotContainsString(
            'Undefined property',
            $html
        );
    }

    public function test_latest_pdf_is_returned(): void
    {
        $quotation = $this->makeOpportunityQuotation();

        $doc = app(QuotationPdfService::class)->generate($quotation);

        $this->assertSame(
            $doc->id,
            app(QuotationPdfService::class)->getLatestPdf($quotation)->id
        );
    }
}
