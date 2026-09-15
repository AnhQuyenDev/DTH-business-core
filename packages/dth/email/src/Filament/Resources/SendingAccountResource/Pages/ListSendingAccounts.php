<?php

namespace Dth\Email\Filament\Resources\SendingAccountResource\Pages;

use Dth\Email\Filament\Resources\SendingAccountResource;
use Dth\Email\Filament\Support\EmailPageUi;
use Dth\Email\Support\UiText;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListSendingAccounts extends ListRecords
{
    protected static string $resource = SendingAccountResource::class;

    public function getTitle(): string|Htmlable
    {
        return EmailPageUi::title(
            UiText::get('account.list.title', 'Sending Accounts'),
            'account',
            'green',
        );
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('account.list.subheading', 'Manage SMTP sending identities and monitor connection status.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(
                    UiText::get('account.create.title', 'Create Sending Account')
                )
                ->icon('heroicon-o-plus'),
        ];
    }
}
