<?php

namespace Dth\Commercial\Filament\Pages;

use Dth\Commercial\Filament\Navigation\CommercialNavigationGroup;
use Dth\Commercial\Services\CommercialAnalyticsService;
use Dth\Commercial\Services\CommercialInsightService;
use Dth\Commercial\Support\CommercialAuthorization;
use Dth\Commercial\Support\UiText;
use Filament\Actions\Action;
use Filament\Pages\Dashboard;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

final class CommercialOverview extends Dashboard
{
    protected static string $routePath = 'commercial-overview';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';
    protected static string|\UnitEnum|null $navigationGroup = CommercialNavigationGroup::Commercial;
    protected static ?int $navigationSort = 0;

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.overview', 'Commercial overview', context: 'navigation');
    }

    public function getTitle(): string|Htmlable
    {
        return UiText::get('overview.title', 'Services & Commercial');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('overview.subheading', 'Manage service catalog health, offer packages, opportunity movement and pipeline performance in one place.');
    }

    protected function getHeaderActions(): array
    {
        if (! config('dth-commercial.features.analytics', true)) {
            return [];
        }

        return [
            Action::make('commercialInsights')
                ->label(UiText::get('reports.analysis', 'Statistical analysis'))
                ->icon('heroicon-o-light-bulb')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-com-report-action dth-com-report-action--analysis'])
                ->modalHeading(UiText::get('reports.analysis_heading', 'Commercial statistical analysis'))
                ->modalDescription(UiText::get('reports.analysis_description', 'Signals calculated from the current catalog and opportunity pipeline.'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(UiText::get('common.actions.close', 'Close'))
                ->modalWidth('4xl')
                ->modalContent(function () {
                    $snapshot = app(CommercialAnalyticsService::class)->snapshot();

                    return view('dth-commercial::filament.modals.commercial-insights', [
                        'snapshot' => $snapshot,
                        'insights' => app(CommercialInsightService::class)->insights($snapshot),
                    ]);
                }),
            Action::make('commercialReportPdf')
                ->label(UiText::get('reports.export_pdf', 'PDF report'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-com-report-action dth-com-report-action--pdf'])
                ->url(fn (): string => route('dth.commercial.reports.dashboard', ['format' => 'pdf'])),
            Action::make('commercialReportXlsx')
                ->label(UiText::get('reports.export_xlsx', 'Excel'))
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-com-report-action dth-com-report-action--excel'])
                ->url(fn (): string => route('dth.commercial.reports.dashboard', ['format' => 'xlsx'])),
            Action::make('commercialReportCsv')
                ->label(UiText::get('reports.export_csv', 'CSV'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-com-report-action dth-com-report-action--csv'])
                ->url(fn (): string => route('dth.commercial.reports.dashboard', ['format' => 'csv'])),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            SchemaView::make('dth-commercial::filament.pages.commercial-overview')
                ->viewData([
                    'snapshot' => app(CommercialAnalyticsService::class)->snapshot(),
                    'features' => [
                        'catalog' => (bool) config('dth-commercial.features.catalog', true),
                        'packages' => (bool) config('dth-commercial.features.packages', true),
                        'opportunities' => (bool) config('dth-commercial.features.opportunities', true),
                    ],
                ]),
        ]);
    }

    public static function canAccess(): bool
    {
        return app(CommercialAuthorization::class)->allows('view');
    }
}
