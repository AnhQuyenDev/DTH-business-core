<?php

namespace Dth\Email\Filament\Resources\EmailCampaignResource\Pages;

use Dth\Email\Filament\Resources\EmailCampaignResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmailCampaigns extends ListRecords
{
    protected static string $resource =
        EmailCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New')
                ->icon('heroicon-o-plus'),
        ];
    }
}