<?php

namespace Dth\Email\Filament\Resources\EmailCampaignResource\Pages;

use Dth\Email\Enums\EmailCampaignStatus;
use Dth\Email\Filament\Resources\EmailCampaignResource;
use Dth\Email\Filament\Resources\EmailCampaignResource\Widgets\CampaignAnalyticsWidget;
use Dth\Email\Filament\Resources\EmailCampaignResource\Widgets\CampaignEngagementFunnelChart;
use Dth\Email\Filament\Resources\EmailCampaignResource\Widgets\CampaignPerformanceTrendChart;
use Dth\Email\Filament\Resources\EmailCampaignResource\Widgets\CampaignReportSummaryWidget;
use Dth\Email\Filament\Resources\EmailCampaignResource\Widgets\CampaignTopLinksTableWidget;
use Dth\Email\Support\UiText;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEmailCampaign extends ViewRecord
{
    protected static string $resource = EmailCampaignResource::class;

    public function getTitle(): string
    {
        return UiText::get('reports.campaign_title', 'Campaign Report').': '.$this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('campaignReportPdf')
                ->label(UiText::get('reports.export_pdf', 'PDF report'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->url(fn (): string => route(
                    'dth.email.reports.campaign.pdf',
                    ['campaign' => $this->record->getKey()],
                ))
                ->visible(fn (): bool => (bool) config('dth-email.reports.pdf.enabled', true)),

            Action::make('campaignRecipientsXlsx')
                ->label(UiText::get('reports.export_xlsx', 'Excel'))
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(fn (): string => route(
                    'dth.email.reports.campaign.xlsx',
                    ['campaign' => $this->record->getKey()],
                )),

            Action::make('campaignRecipientsCsv')
                ->label(UiText::get('reports.export_csv', 'CSV'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn (): string => route(
                    'dth.email.reports.campaign.csv',
                    ['campaign' => $this->record->getKey()],
                )),

            EditAction::make()
                ->label(UiText::get('common.actions.edit', 'Edit'))
                ->icon('heroicon-o-pencil-square')
                ->visible(fn (): bool => $this->record->status === EmailCampaignStatus::Draft),

            DeleteAction::make()
                ->label(UiText::get('common.actions.delete', 'Delete'))
                ->icon('heroicon-o-trash')
                ->visible(fn (): bool => $this->record->status === EmailCampaignStatus::Draft),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        $campaignId = (int) $this->record->getKey();

        return [
            CampaignReportSummaryWidget::make([
                'campaignId' => $campaignId,
            ]),
            CampaignAnalyticsWidget::make([
                'campaignId' => $campaignId,
            ]),
            CampaignPerformanceTrendChart::make([
                'campaignId' => $campaignId,
            ]),
            CampaignEngagementFunnelChart::make([
                'campaignId' => $campaignId,
            ]),
            CampaignTopLinksTableWidget::make([
                'campaignId' => $campaignId,
            ]),
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
