<?php

namespace Dth\Email\Filament\Pages;

use Carbon\CarbonImmutable;
use Dth\Email\Enums\EmailCampaignStatus;
use Dth\Email\Filament\Navigation\EmailNavigationGroup;
use Dth\Email\Filament\Resources\EmailCampaignResource;
use Dth\Email\Filament\Widgets\Dashboard\EmailEngagementFunnelChart;
use Dth\Email\Filament\Widgets\Dashboard\EmailOverviewStats;
use Dth\Email\Filament\Widgets\Dashboard\EmailPerformanceTrendChart;
use Dth\Email\Filament\Widgets\Dashboard\SendingAccountPerformanceChart;
use Dth\Email\Filament\Widgets\Dashboard\TopCampaignsChart;
use Dth\Email\Filament\Widgets\Dashboard\TopLinksChart;
use Dth\Email\Models\SendingAccount;
use Dth\Email\Services\EmailDashboardFilterResolver;
use Dth\Email\Services\EmailInsightService;
use Dth\Email\Support\UiText;
use Filament\Pages\Dashboard;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class EmailDashboard extends Dashboard
{
    protected static string $routePath = 'email-dashboard';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string|\UnitEnum|null $navigationGroup = EmailNavigationGroup::Email;
    protected static ?int $navigationSort = 0;

    public static function canAccess(): bool
    {
        return EmailCampaignResource::canViewAny();
    }

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.dashboard', 'Tổng quan Email', context: 'navigation');
    }

    public function getTitle(): string|Htmlable
    {
        return UiText::get('dashboard.title', 'Tổng quan Email');
    }

    public function getSubheading(): string|Htmlable|null
    {
        $filters = app(EmailDashboardFilterResolver::class)->resolveRequest(request());

        return UiText::get(
            'dashboard.subheading',
            'Theo dõi hiệu suất email marketing từ :start đến :end.',
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
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'md' => 6,
            'xl' => 12,
        ];
    }

    public function getWidgetsContentComponent(): Component
    {
        return Grid::make($this->getColumns())
            ->schema(fn (): array => $this->getWidgetsSchemaComponents($this->getWidgets()))
            ->extraAttributes(['class' => 'dth-email-widgets-grid']);
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

    /** @return array<string, mixed> */
    private function filterViewData(): array
    {
        $filters = app(EmailDashboardFilterResolver::class)->resolveRequest(request());
        $query = array_filter(
            request()->query(),
            static fn (mixed $value): bool => ! ($value === null || $value === ''),
        );

        return [
            'actionUrl' => static::getUrl(),
            'resetUrl' => static::getUrl(),
            'state' => $this->normalizedFilterState(),
            'activePreset' => $this->resolveActivePreset(),
            'presets' => $this->presetLinks(),
            'sendingAccounts' => SendingAccount::query()
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all(),
            'campaignStatuses' => collect(EmailCampaignStatus::cases())
                ->mapWithKeys(fn (EmailCampaignStatus $status): array => [
                    $status->value => UiText::status($status),
                ])
                ->all(),
            'exportUrls' => [
                'pdf' => route('dth.email.reports.dashboard.pdf', $query),
                'xlsx' => route('dth.email.reports.dashboard.xlsx', $query),
                'csv' => route('dth.email.reports.dashboard.csv', $query),
            ],
            'pdfEnabled' => (bool) config('dth-email.reports.pdf.enabled', true),
            'insightReport' => app(EmailInsightService::class)->dashboard($filters),
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

    /** @return array<string, array{label: string, url: string}> */
    private function presetLinks(): array
    {
        $today = CarbonImmutable::today();

        return [
            '7d' => [
                'label' => UiText::get('dashboard.filters.presets.7d', '7 ngày qua'),
                'url' => $this->buildFilterUrl([
                    'start_date' => $today->subDays(6)->format('Y-m-d'),
                    'end_date' => $today->format('Y-m-d'),
                ]),
            ],
            '30d' => [
                'label' => UiText::get('dashboard.filters.presets.30d', '30 ngày qua'),
                'url' => $this->buildFilterUrl([
                    'start_date' => $today->subDays(29)->format('Y-m-d'),
                    'end_date' => $today->format('Y-m-d'),
                ]),
            ],
            '90d' => [
                'label' => UiText::get('dashboard.filters.presets.90d', '90 ngày qua'),
                'url' => $this->buildFilterUrl([
                    'start_date' => $today->subDays(89)->format('Y-m-d'),
                    'end_date' => $today->format('Y-m-d'),
                ]),
            ],
            'month' => [
                'label' => UiText::get('dashboard.filters.presets.month', 'Tháng này'),
                'url' => $this->buildFilterUrl([
                    'start_date' => $today->startOfMonth()->format('Y-m-d'),
                    'end_date' => $today->format('Y-m-d'),
                ]),
            ],
        ];
    }

    private function resolveActivePreset(): ?string
    {
        $state = $this->normalizedFilterState();
        $today = CarbonImmutable::today();
        $start = $state['start_date'] ?? null;
        $end = $state['end_date'] ?? null;

        $presets = [
            '7d' => [$today->subDays(6)->format('Y-m-d'), $today->format('Y-m-d')],
            '30d' => [$today->subDays(29)->format('Y-m-d'), $today->format('Y-m-d')],
            '90d' => [$today->subDays(89)->format('Y-m-d'), $today->format('Y-m-d')],
            'month' => [$today->startOfMonth()->format('Y-m-d'), $today->format('Y-m-d')],
        ];

        foreach ($presets as $key => [$presetStart, $presetEnd]) {
            if ($start === $presetStart && $end === $presetEnd) {
                return $key;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $overrides */
    private function buildFilterUrl(array $overrides = []): string
    {
        $state = array_merge($this->normalizedFilterState(), $overrides);

        $query = array_filter($state, static fn (mixed $value): bool => ! ($value === null || $value === ''));
        $query['compare_previous'] = ($state['compare_previous'] ?? true) ? '1' : '0';

        return static::getUrl($query);
    }
}
