<?php

namespace Dth\Email\Filament\Resources\EmailCampaignResource\Pages;

use Dth\Email\Filament\Resources\EmailCampaignResource;
use Dth\Email\Filament\Support\EmailPageUi;
use Dth\Email\Support\UiText;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditEmailCampaign extends EditRecord
{
    protected static string $resource = EmailCampaignResource::class;

    public function getTitle(): string|Htmlable
    {
        return EmailPageUi::title(
            UiText::get('campaign.edit.title', 'Edit Email Campaign'),
            'campaign',
        );
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get(
            'campaign.edit.subheading',
            'Update campaign content and sending configuration for the selected campaign.'
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label(UiText::get('common.actions.view', 'View'))
                ->icon('heroicon-o-eye'),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label(UiText::get('common.actions.save', 'Save'))
                ->icon('heroicon-o-check'),
            $this->getCancelFormAction()
                ->label(UiText::get('common.actions.cancel', 'Cancel'))
                ->icon('heroicon-o-x-mark')
                ->color('gray'),
        ];
    }
}
