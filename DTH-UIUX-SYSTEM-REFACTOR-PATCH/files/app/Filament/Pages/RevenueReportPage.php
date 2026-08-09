<?php

namespace App\Filament\Pages;

use App\Models\Crm\Staff;
use App\Models\Marketing\Campaign;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\MarketingCampaign;
use App\Models\Sales\Service;
use App\Models\Sales\ServicePackage;
use App\Services\Finance\RevenueReportService;
use Filament\Pages\Page;

class RevenueReportPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?int $navigationSort = 30;
    protected static string $view = 'filament.pages.revenue-report';

    public string $startDate = '';
    public string $endDate = '';
    public ?int $marketingCampaignId = null;
    public ?int $emailCampaignId = null;
    public ?int $landingPageId = null;
    public string $utmSource = '';
    public string $utmMedium = '';
    public string $utmCampaign = '';
    public string $utmContent = '';
    public ?int $serviceId = null;
    public ?int $servicePackageId = null;
    public ?int $salesStaffId = null;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();
    }

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.finance');
    }
    public static function getNavigationLabel(): string { return __('finance.revenue_report'); }
    public function getTitle(): string { return __('finance.revenue_report'); }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && (
            $user->isAdmin()
            || $user->canReadAcrossBusiness()
            || $user->isFinanceStaff()
            || $user->isSalesManager()
            || $user->isMarketingManager()
        );
    }

    public function resetFilters(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();
        $this->marketingCampaignId = null;
        $this->emailCampaignId = null;
        $this->landingPageId = null;
        $this->utmSource = '';
        $this->utmMedium = '';
        $this->utmCampaign = '';
        $this->utmContent = '';
        $this->serviceId = null;
        $this->servicePackageId = null;
        $this->salesStaffId = null;
    }

    public function getFiltersProperty(): array
    {
        return [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'marketing_campaign_id' => $this->marketingCampaignId,
            'email_campaign_id' => $this->emailCampaignId,
            'landing_page_id' => $this->landingPageId,
            'utm_source' => $this->utmSource,
            'utm_medium' => $this->utmMedium,
            'utm_campaign' => $this->utmCampaign,
            'utm_content' => $this->utmContent,
            'service_id' => $this->serviceId,
            'service_package_id' => $this->servicePackageId,
            'sales_staff_id' => $this->salesStaffId,
        ];
    }

    public function getReportProperty(): array
    {
        $service = app(RevenueReportService::class);
        $filters = $this->filters;

        return [
            'summary' => $service->summary($filters),
            'marketing_campaigns' => $service->marketingCampaigns($filters),
            'email_campaigns' => $service->emailCampaigns($filters),
            'sources' => $service->sources($filters),
            'utm_campaigns' => $service->utmCampaigns($filters),
            'landing_pages' => $service->landingPages($filters),
            'services' => $service->services($filters),
            'packages' => $service->packages($filters),
            'sales' => $service->sales($filters),
            'customers' => $service->customers($filters),
        ];
    }

    public function getOptionsProperty(): array
    {
        return [
            'marketing_campaigns' => MarketingCampaign::query()->orderBy('name')->pluck('name', 'id')->all(),
            'email_campaigns' => Campaign::query()->orderByDesc('created_at')->pluck('name', 'id')->all(),
            'landing_pages' => LandingPage::query()->orderBy('name')->pluck('name', 'id')->all(),
            'services' => Service::query()->orderBy('name')->pluck('name', 'id')->all(),
            'packages' => ServicePackage::query()->orderBy('name')->pluck('name', 'id')->all(),
            'sales_staff' => Staff::query()->whereHas('department', fn ($q) => $q->where('function_key', 'sales'))->orderBy('full_name')->pluck('full_name', 'id')->all(),
        ];
    }
}
