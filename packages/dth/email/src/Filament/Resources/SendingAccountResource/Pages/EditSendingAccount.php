<?php

namespace Dth\Email\Filament\Resources\SendingAccountResource\Pages;

use Dth\Email\DTO\SmtpAccountData;
use Dth\Email\Filament\Resources\SendingAccountResource;
use Dth\Email\Support\UiText;
use Dth\Email\Services\SendingAccountService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSendingAccount extends EditRecord
{
    protected static string $resource = SendingAccountResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $config = $this->record->encrypted_config ?? [];

        return [
            ...$data,
            'smtp_host' => $config['host'] ?? null,
            'smtp_port' => $config['port'] ?? 587,
            'smtp_scheme' => $config['scheme'] ?? 'smtp',
            'smtp_username' => $config['username'] ?? null,
            'smtp_password' => null,
            'smtp_timeout' => $config['timeout'] ?? 30,
            'smtp_local_domain' => $config['local_domain'] ?? null,
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $current = $record->encrypted_config ?? [];

        $smtp = SmtpAccountData::fromArray([
            'host' => $data['smtp_host'],
            'port' => $data['smtp_port'],
            'scheme' => $data['smtp_scheme'],
            'username' => $data['smtp_username'] ?? null,
            'password' => filled($data['smtp_password'] ?? null) ? $data['smtp_password'] : ($current['password'] ?? null),
            'timeout' => $data['smtp_timeout'] ?? 30,
            'local_domain' => $data['smtp_local_domain'] ?? null,
        ]);

        return app(SendingAccountService::class)->update($record, $data, $smtp);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label(UiText::get('common.actions.delete', 'Delete'))
                ->icon('heroicon-o-trash'),
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
