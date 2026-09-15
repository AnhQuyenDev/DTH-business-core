<?php

namespace Dth\Marketing\Filament\Pages;

use Carbon\CarbonImmutable;
use Dth\Marketing\DTO\MarketingAnalyticsFilter;
use Dth\Marketing\Enums\MarketingCampaignStatus;
use Dth\Marketing\Filament\Navigation\MarketingNavigationGroup;
use Dth\Marketing\Filament\Support\MarketingPageUi;
use Dth\Marketing\Models\MarketingCampaign;
use Dth\Marketing\Services\MarketingAnalyticsService;
use Dth\Marketing\Support\IntegrationHealthService;
use Dth\Marketing\Support\MarketingAuthorizationService;
use Dth\Marketing\Support\UiText;
use Filament\Actions\Action;
use Filament\Pages\Dashboard;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class MarketingOverview extends Dashboard
{
    protected static string $routePath = 'marketing-dashboard';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string|\UnitEnum|null $navigationGroup = MarketingNavigationGroup::Marketing;
    protected static ?int $navigationSort = 0;

    /** @var array<string, mixed>|null */
    private ?array $analyticsCache = null;

    public static function canAccess(): bool
    {
        return app(MarketingAuthorizationService::class)->reports(auth()->user());
    }

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.dashboard', 'Marketing overview', context: 'navigation');
    }

    public function getTitle(): string|Htmlable
    {
        return MarketingPageUi::title(UiText::get('analytics.title', 'Marketing Analytics'), 'report');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get(
            'analytics.subheading',
            'Performance from :start to :end. Financial KPIs are capability-aware and display N/A when Finance is unavailable.',
            [
                'start' => $this->filter()->start->format('d/m/Y'),
                'end' => $this->filter()->end->format('d/m/Y'),
            ],
        );
    }

    protected function getHeaderActions(): array
    {
        $query = $this->filter()->toQuery();

        return [
            Action::make('marketingReportPdf')
                ->label(UiText::get('reports.export_pdf', 'PDF report'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->extraAttributes([
                    'class' => 'dth-mkt-export-action dth-mkt-export-action--pdf',
                ])
                ->url(fn (): string => route(
                    'dth.marketing.reports.dashboard',
                    [...$query, 'format' => 'pdf']
                ))
                ->visible(
                    fn (): bool => app(MarketingAuthorizationService::class)
                        ->export(auth()->user())
                ),

            Action::make('marketingReportXlsx')
                ->label(UiText::get('reports.export_xlsx', 'Excel'))
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->extraAttributes([
                    'class' => 'dth-mkt-export-action dth-mkt-export-action--excel',
                ])
                ->url(fn (): string => route(
                    'dth.marketing.reports.dashboard',
                    [...$query, 'format' => 'xlsx']
                ))
                ->visible(
                    fn (): bool => app(MarketingAuthorizationService::class)
                        ->export(auth()->user())
                ),

            Action::make('marketingReportCsv')
                ->label(UiText::get('reports.export_csv', 'CSV'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->extraAttributes([
                    'class' => 'dth-mkt-export-action dth-mkt-export-action--csv',
                ])
                ->url(fn (): string => route(
                    'dth.marketing.reports.dashboard',
                    [...$query, 'format' => 'csv']
                ))
                ->visible(
                    fn (): bool => app(MarketingAuthorizationService::class)
                        ->export(auth()->user())
                ),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            SchemaView::make('dth-marketing::filament.pages.marketing-dashboard-filters')
                ->viewData($this->filterViewData()),
            SchemaView::make('dth-marketing::filament.pages.marketing-dashboard')
                ->viewData([
                    'analytics' => $this->analytics(),
                    'health' => app(IntegrationHealthService::class)->snapshot(),
                ]),
        ]);
    }

    /** @return array<string, mixed> */
    private function analytics(): array
    {
        return $this->analyticsCache ??= app(MarketingAnalyticsService::class)->dashboard($this->filter());
    }

    private function filter(): MarketingAnalyticsFilter
    {
        return MarketingAnalyticsFilter::fromArray(request()->query());
    }

    /** @return array<string, mixed> */
    private function filterViewData(): array
    {
        $filter = $this->filter();

        return [
            'actionUrl' => static::getUrl(),
            'resetUrl' => static::getUrl(),
            'filter' => $filter,
            'state' => [
                'period' => $filter->period,
                'start' => $filter->start->toDateString(),
                'end' => $filter->end->toDateString(),
                'campaign' => $filter->campaignId,
                'status' => $filter->campaignStatus,
                'source' => $filter->source,
            ],
            'presets' => $this->presetLinks($filter),
            'campaigns' => MarketingCampaign::query()->orderByDesc('created_at')->pluck('name', 'id')->all(),
            'statuses' => MarketingCampaignStatus::options(),
            'sources' => app(MarketingAnalyticsService::class)->sourceOptions($filter),
        ];
    }

    /** @return array<string, array{label:string,url:string}> */
    private function presetLinks(MarketingAnalyticsFilter $filter): array
    {
        $scope = array_filter([
            'campaign' => $filter->campaignId,
            'status' => $filter->campaignStatus,
            'source' => $filter->source,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        return [
            '7d' => ['label' => UiText::get('analytics.period.7d', '7 days'), 'url' => static::getUrl([...$scope, 'period' => '7d'])],
            '30d' => ['label' => UiText::get('analytics.period.30d', '30 days'), 'url' => static::getUrl([...$scope, 'period' => '30d'])],
            '90d' => ['label' => UiText::get('analytics.period.90d', '90 days'), 'url' => static::getUrl([...$scope, 'period' => '90d'])],
            'month' => ['label' => UiText::get('analytics.period.month', 'This month'), 'url' => static::getUrl([...$scope, 'period' => 'month'])],
        ];
    }
}
