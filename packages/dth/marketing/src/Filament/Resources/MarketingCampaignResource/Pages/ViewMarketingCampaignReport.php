<?php

namespace Dth\Marketing\Filament\Resources\MarketingCampaignResource\Pages;

use Dth\Marketing\DTO\MarketingAnalyticsFilter;
use Dth\Marketing\Filament\Resources\MarketingCampaignResource;
use Dth\Marketing\Filament\Support\MarketingPageUi;
use Dth\Marketing\Models\MarketingCampaign;
use Dth\Marketing\Services\MarketingAnalyticsService;
use Dth\Marketing\Support\IntegrationHealthService;
use Dth\Marketing\Support\MarketingAuthorizationService;
use Dth\Marketing\Support\UiText;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class ViewMarketingCampaignReport extends ViewRecord
{
    protected static string $resource = MarketingCampaignResource::class;

    /** @var array<string, mixed>|null */
    private ?array $reportCache = null;

    public function getTitle(): string|Htmlable
    {
        return MarketingPageUi::title(UiText::get('analytics.campaign_report', 'Campaign Report').': '.$this->getRecord()->name, 'report');
    }

    public function getSubheading(): string|Htmlable|null
    {
        $filter = $this->filter();

        return UiText::get('analytics.campaign_report_period', 'Performance from :start to :end.', [
            'start' => $filter->start->format('d/m/Y'),
            'end' => $filter->end->format('d/m/Y'),
        ]);
    }

    protected function getHeaderActions(): array
    {
        $campaign = $this->getRecord();
        $query = $this->filter()->toQuery();

        return [
            Action::make('backToEdit')
                ->label(UiText::get('common.actions.edit', 'Edit'))
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->url(fn (): string => MarketingCampaignResource::getUrl('edit', ['record' => $campaign]))
                ->visible(fn (): bool => MarketingCampaignResource::canEdit($campaign)),
            Action::make('campaignReportPdf')
                ->label(UiText::get('reports.export_pdf', 'PDF report'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-mkt-export-action dth-mkt-export-action--pdf'])
                ->url(fn (): string => route('dth.marketing.reports.campaign', ['campaign' => $campaign, 'format' => 'pdf', ...$query]))
                ->visible(fn (): bool => app(MarketingAuthorizationService::class)->export(auth()->user())),
            Action::make('campaignReportXlsx')
                ->label(UiText::get('reports.export_xlsx', 'Excel'))
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-mkt-export-action dth-mkt-export-action--excel'])
                ->url(fn (): string => route('dth.marketing.reports.campaign', ['campaign' => $campaign, 'format' => 'xlsx', ...$query]))
                ->visible(fn (): bool => app(MarketingAuthorizationService::class)->export(auth()->user())),
            Action::make('campaignReportCsv')
                ->label(UiText::get('reports.export_csv', 'CSV'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-mkt-export-action dth-mkt-export-action--csv'])
                ->url(fn (): string => route('dth.marketing.reports.campaign', ['campaign' => $campaign, 'format' => 'csv', ...$query]))
                ->visible(fn (): bool => app(MarketingAuthorizationService::class)->export(auth()->user())),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            SchemaView::make('dth-marketing::filament.pages.marketing-campaign-report')
                ->viewData([
                    'report' => $this->report(),
                    'campaign' => $this->getRecord(),
                    'filter' => $this->filter(),
                    'actionUrl' => MarketingCampaignResource::getUrl('view', ['record' => $this->getRecord()]),
                    'health' => app(IntegrationHealthService::class)->snapshot(),
                ]),
        ]);
    }

    public static function canAccess(array $parameters = []): bool
    {
        return app(MarketingAuthorizationService::class)->reports(auth()->user());
    }

    /** @return array<string, mixed> */
    private function report(): array
    {
        return $this->reportCache ??= app(MarketingAnalyticsService::class)->campaignReport(
            $this->getRecord(),
            $this->filter(),
        );
    }

    private function filter(): MarketingAnalyticsFilter
    {
        $input = request()->query();
        $input['campaign'] = $this->getRecord()->getKey();

        return MarketingAnalyticsFilter::fromArray($input);
    }

    /** @return MarketingCampaign */
    public function getRecord(): MarketingCampaign
    {
        /** @var MarketingCampaign $record */
        $record = parent::getRecord();

        return $record;
    }
}
