<?php

namespace Dth\Email\Filament\Resources\EmailCampaignResource\Pages;

use Dth\Email\Enums\EmailCampaignStatus;
use Dth\Email\Filament\Resources\EmailCampaignResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Dth\Email\Filament\Resources\EmailCampaignResource\Widgets\CampaignAnalyticsWidget;

class ViewEmailCampaign extends ViewRecord
{
    protected static string $resource =
        EmailCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit')
                ->icon(
                    'heroicon-o-pencil-square'
                )
                ->visible(
                    fn (): bool =>
                        $this->record->status
                        === EmailCampaignStatus::Draft
                ),

            DeleteAction::make()
                ->label('Delete')
                ->icon('heroicon-o-trash')
                ->visible(
                    fn (): bool =>
                        $this->record->status
                        === EmailCampaignStatus::Draft
                ),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CampaignAnalyticsWidget::make([
                'campaignId' => (int) $this->record->getKey(),
            ]),
        ];
    }
}