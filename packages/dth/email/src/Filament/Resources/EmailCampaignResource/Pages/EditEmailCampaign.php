<?php

namespace Dth\Email\Filament\Resources\EmailCampaignResource\Pages;

use Dth\Email\Filament\Resources\EmailCampaignResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEmailCampaign extends EditRecord
{
    protected static string $resource =
        EmailCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('View')
                ->icon('heroicon-o-eye'),
        ];
    }
}