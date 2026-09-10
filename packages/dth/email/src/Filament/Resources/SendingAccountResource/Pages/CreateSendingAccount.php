<?php

namespace Dth\Email\Filament\Resources\SendingAccountResource\Pages;

use Dth\Email\DTO\SmtpAccountData;
use Dth\Email\Filament\Resources\SendingAccountResource;
use Dth\Email\Support\UiText;
use Dth\Email\Services\SendingAccountService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSendingAccount extends CreateRecord
{
    protected static string $resource = SendingAccountResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $smtp = SmtpAccountData::fromArray([
            'host' => $data['smtp_host'],
            'port' => $data['smtp_port'],
            'scheme' => $data['smtp_scheme'],
            'username' => $data['smtp_username'] ?? null,
            'password' => $data['smtp_password'] ?? null,
            'timeout' => $data['smtp_timeout'] ?? 30,
            'local_domain' => $data['smtp_local_domain'] ?? null,
        ]);

        return app(SendingAccountService::class)->create($data, $smtp);
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label(UiText::get('common.actions.save', 'Save'))
                ->icon('heroicon-o-check'),
            $this->getCancelFormAction()
                ->label(UiText::get('common.actions.cancel', 'Cancel'))
                ->icon('heroicon-o-x-mark')
                ->color('gray'),
        ];
    }
}
