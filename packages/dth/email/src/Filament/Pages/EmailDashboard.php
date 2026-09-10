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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Illuminate\Contracts\Support\Htmlable;

class EmailDashboard extends Dashboard
{
    use HasFiltersAction;

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
        $filters = app(EmailDashboardFilterResolver::class)->resolve($this->filters);

        return UiText::get(
            'dashboard.subheading',
            'Performance from :start to :end. Use Filters to refine the report.',
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

    protected function getHeaderActions(): array
    {
        $defaultDays = max(1, (int) config('dth-email.analytics.default_range_days', 30));

        return [
            FilterAction::make()
                ->label(UiText::get('dashboard.filters.action', 'Filters'))
                ->modalHeading(UiText::get('dashboard.filters.heading', 'Dashboard filters'))
                ->modalSubmitActionLabel(UiText::get('dashboard.filters.apply', 'Apply filters'))
                ->schema([
                    DatePicker::make('start_date')
                        ->label(UiText::get('dashboard.filters.start_date', 'Start date'))
                        ->default(now()->subDays($defaultDays - 1)->startOfDay())
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->maxDate(now())
                        ->required(),

                    DatePicker::make('end_date')
                        ->label(UiText::get('dashboard.filters.end_date', 'End date'))
                        ->default(now()->endOfDay())
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->maxDate(now())
                        ->rule('after_or_equal:start_date')
                        ->required(),

                    Select::make('sending_account_id')
                        ->label(UiText::get('dashboard.filters.sending_account', 'Sending account'))
                        ->options(fn (): array => SendingAccount::query()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->placeholder(UiText::get('dashboard.filters.all_accounts', 'All sending accounts')),

                    Select::make('campaign_status')
                        ->label(UiText::get('dashboard.filters.campaign_status', 'Campaign status'))
                        ->options(collect(EmailCampaignStatus::cases())
                            ->mapWithKeys(fn (EmailCampaignStatus $status): array => [
                                $status->value => UiText::status($status),
                            ])
                            ->all())
                        ->native(false)
                        ->placeholder(UiText::get('dashboard.filters.all_statuses', 'All statuses')),

                    Toggle::make('compare_previous')
                        ->label(UiText::get('dashboard.filters.compare_previous', 'Compare with previous period'))
                        ->default(true)
                        ->inline(false),
                ]),
        ];
    }
}
