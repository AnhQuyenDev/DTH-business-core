<?php

namespace Dth\Email\Filament\Pages;

use Dth\Email\Enums\EmailCampaignStatus;
use Dth\Email\Filament\Navigation\EmailNavigationGroup;
use Dth\Email\Filament\Widgets\Dashboard\EmailEngagementFunnelChart;
use Dth\Email\Filament\Widgets\Dashboard\EmailInfrastructureOverview;
use Dth\Email\Filament\Widgets\Dashboard\EmailOverviewStats;
use Dth\Email\Filament\Widgets\Dashboard\EmailPerformanceTrendChart;
use Dth\Email\Filament\Widgets\Dashboard\SendingAccountPerformanceChart;
use Dth\Email\Filament\Widgets\Dashboard\TopCampaignsChart;
use Dth\Email\Filament\Widgets\Dashboard\TopLinksChart;
use Dth\Email\Models\SendingAccount;
use Dth\Email\Services\EmailDashboardFilterResolver;
use Dth\Email\Support\UiText;
use Filament\Pages\Dashboard;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class EmailDashboard extends Dashboard
{
    protected static string $routePath = 'email-dashboard';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string|\UnitEnum|null $navigationGroup = EmailNavigationGroup::Email;
    protected static ?int $navigationSort = 0;

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.dashboard', 'Dashboard', context: 'navigation');
    }

    public function getTitle(): string|Htmlable
    {
        return UiText::get('dashboard.title', 'Email Analytics');
    }

    public function getSubheading(): string|Htmlable|null
    {
        $filters = app(EmailDashboardFilterResolver::class)->resolveRequest(request());

        return UiText::get(
            'dashboard.subheading',
            'Performance from :start to :end. Use the filters below to refine the report.',
            [
                'start' => $filters->range->start->format('d/m/Y'),
                'end' => $filters->range->end->format('d/m/Y'),
            ],
        );
    }

    /** @return array<class-string<\Filament\Widgets\Widget>> */
    public function getWidgets(): array
    {
        return [
            EmailOverviewStats::class,
            EmailPerformanceTrendChart::class,
            EmailEngagementFunnelChart::class,
            TopCampaignsChart::class,
            TopLinksChart::class,
            SendingAccountPerformanceChart::class,
            EmailInfrastructureOverview::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'md' => 6,
            'xl' => 12,
        ];
    }

    /** @return array<string, mixed> */
    public function getWidgetData(): array
    {
        return [
            'dashboardFilters' => $this->normalizedFilterState(),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                SchemaView::make('dth-email::filament.pages.email-dashboard-filters')
                    ->viewData($this->filterViewData()),
                $this->getWidgetsContentComponent(),
            ]);
    }

    /**
     * Dashboard filters deliberately use a normal GET request instead of a
     * Livewire action. The dashboard is a read-only reporting surface, so a
     * URL-based filter state is simpler, shareable/bookmarkable, and avoids
     * creating an unnecessary /livewire/update dependency for filtering.
     *
     * @return array<string, mixed>
     */
    private function filterViewData(): array
    {
        return [
            'actionUrl' => static::getUrl(),
            'resetUrl' => static::getUrl(),
            'state' => $this->normalizedFilterState(),
            'sendingAccounts' => SendingAccount::query()
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all(),
            'campaignStatuses' => collect(EmailCampaignStatus::cases())
                ->mapWithKeys(fn (EmailCampaignStatus $status): array => [
                    $status->value => UiText::status($status),
                ])
                ->all(),
        ];
    }
    /** @return array<string, mixed> */
    private function normalizedFilterState(): array
    {
        $filters = app(EmailDashboardFilterResolver::class)->resolveRequest(request());

        return [
            'start_date' => $filters->range->start->format('Y-m-d'),
            'end_date' => $filters->range->end->format('Y-m-d'),
            'sending_account_id' => $filters->sendingAccountId,
            'campaign_status' => $filters->campaignStatusValue(),
            'compare_previous' => $filters->comparePrevious,
        ];
    }

}
