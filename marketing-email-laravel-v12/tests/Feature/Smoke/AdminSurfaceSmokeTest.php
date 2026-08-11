<?php

namespace Tests\Feature\Smoke;

use App\Filament\Pages\AdminDashboard;
use App\Filament\Pages\CampaignAnalyticsPage;
use App\Filament\Pages\CampaignReportPage;
use App\Filament\Pages\CompanySettingsPage;
use App\Filament\Pages\CustomerCarePage;
use App\Filament\Pages\CustomerServiceDashboard;
use App\Filament\Pages\FinanceDashboard;
use App\Filament\Pages\MarketingDashboard;
use App\Filament\Pages\RevenueReportPage;
use App\Filament\Pages\RolePermissionMatrix;
use App\Filament\Pages\SalesDashboard;
use App\Filament\Pages\WorkforceAnalyticsPage;
use App\Filament\Resources\AuditLogResource;
use App\Filament\Resources\BusinessContactResource;
use App\Filament\Resources\CampaignResource;
use App\Filament\Resources\CompanyMatchCandidateResource;
use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\ContactListResource;
use App\Filament\Resources\ContactQualificationResource;
use App\Filament\Resources\ContactResource;
use App\Filament\Resources\CustomFieldResource;
use App\Filament\Resources\CustomerDistributionBatchResource;
use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\DepartmentResource;
use App\Filament\Resources\EmailTemplateCategoryResource;
use App\Filament\Resources\EmailTemplateResource;
use App\Filament\Resources\Finance\PaymentResource;
use App\Filament\Resources\FormTemplateResource;
use App\Filament\Resources\LandingPageResource;
use App\Filament\Resources\LandingPageSubmissionResource;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\MarketingCampaignResource;
use App\Filament\Resources\PersonalContactResource;
use App\Filament\Resources\PositionResource;
use App\Filament\Resources\Sales\BankAccountResource;
use App\Filament\Resources\Sales\OpportunityResource;
use App\Filament\Resources\Sales\PaymentTrackingResource;
use App\Filament\Resources\Sales\PriceBookResource;
use App\Filament\Resources\Sales\QuotationApprovalResource;
use App\Filament\Resources\Sales\QuotationResource;
use App\Filament\Resources\Sales\ServicePackageResource;
use App\Filament\Resources\Sales\ServiceProductResource;
use App\Filament\Resources\Sales\ServiceResource;
use App\Filament\Resources\Security\RbacRoleResource;
use App\Filament\Resources\SegmentResource;
use App\Filament\Resources\SendingAccountResource;
use App\Filament\Resources\SendingDomainResource;
use App\Filament\Resources\StaffResource;
use App\Filament\Resources\SupportTicketResource;
use App\Filament\Resources\SuppressionEntryResource;
use App\Filament\Resources\TagResource;
use App\Filament\Resources\UiBadgeStyleResource;
use App\Filament\Resources\UserResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\MakesV1Actors;
use Tests\TestCase;

class AdminSurfaceSmokeTest extends TestCase
{
    use MakesV1Actors;
    use RefreshDatabase;

    #[DataProvider('resourceProvider')]
    public function test_super_admin_can_render_every_registered_resource_index(string $resource): void
    {
        $this->actingAs($this->makeV1SuperAdmin());

        $this->get($resource::getUrl('index'))->assertOk();
    }

    /** @return array<string,array{0:class-string}> */
    public static function resourceProvider(): array
    {
        $resources = [
            AuditLogResource::class,
            BusinessContactResource::class,
            CampaignResource::class,
            CompanyMatchCandidateResource::class,
            CompanyResource::class,
            ContactListResource::class,
            ContactQualificationResource::class,
            ContactResource::class,
            CustomFieldResource::class,
            CustomerDistributionBatchResource::class,
            CustomerResource::class,
            DepartmentResource::class,
            EmailTemplateCategoryResource::class,
            EmailTemplateResource::class,
            PaymentResource::class,
            FormTemplateResource::class,
            LandingPageResource::class,
            LandingPageSubmissionResource::class,
            LeadResource::class,
            MarketingCampaignResource::class,
            PersonalContactResource::class,
            PositionResource::class,
            BankAccountResource::class,
            OpportunityResource::class,
            PaymentTrackingResource::class,
            PriceBookResource::class,
            QuotationApprovalResource::class,
            QuotationResource::class,
            ServicePackageResource::class,
            ServiceProductResource::class,
            ServiceResource::class,
            RbacRoleResource::class,
            SegmentResource::class,
            SendingAccountResource::class,
            SendingDomainResource::class,
            StaffResource::class,
            SupportTicketResource::class,
            SuppressionEntryResource::class,
            TagResource::class,
            UiBadgeStyleResource::class,
            UserResource::class,
        ];

        $cases = [];
        foreach ($resources as $resource) {
            $cases[class_basename($resource)] = [$resource];
        }

        return $cases;
    }

    #[DataProvider('pageProvider')]
    public function test_super_admin_can_render_every_system_or_business_dashboard_page(string $page): void
    {
        $this->actingAs($this->makeV1SuperAdmin());

        $this->get($page::getUrl())->assertOk();
    }

    /** @return array<string,array{0:class-string}> */
    public static function pageProvider(): array
    {
        $pages = [
            AdminDashboard::class,
            CampaignAnalyticsPage::class,
            CampaignReportPage::class,
            CompanySettingsPage::class,
            CustomerCarePage::class,
            CustomerServiceDashboard::class,
            FinanceDashboard::class,
            MarketingDashboard::class,
            RevenueReportPage::class,
            RolePermissionMatrix::class,
            SalesDashboard::class,
            WorkforceAnalyticsPage::class,
        ];

        $cases = [];
        foreach ($pages as $page) {
            $cases[class_basename($page)] = [$page];
        }

        return $cases;
    }
}
