<?php

namespace Dth\Email\Filament\Resources\EmailSuppressionResource\Pages;

use Dth\Email\Enums\SuppressionReason;
use Dth\Email\Filament\Resources\EmailSuppressionResource;
use Dth\Email\Services\SuppressionService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateEmailSuppression extends CreateRecord
{
    protected static string $resource = EmailSuppressionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(SuppressionService::class)->suppress(
            email: $data['email'],
            reason: SuppressionReason::Manual,
            source: 'email.admin',
            note: $data['note'] ?? null,
            createdBy: auth()->id(),
        );
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
