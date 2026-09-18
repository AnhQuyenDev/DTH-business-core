<?php

namespace Dth\Commercial\Filament\Resources\ServiceResource\Pages;

use Dth\Commercial\Filament\Resources\ServiceResource;
use Dth\Commercial\Support\UiText;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    public function getTitle(): string|Htmlable
    {
        return UiText::get('pages.services.create_title', 'Create service');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.services.create_subheading', 'Create a clear service definition that can be reused consistently across campaigns, packages and opportunities.');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label(UiText::get('actions.save_service', 'Save service'))
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->extraAttributes(['class' => 'dth-com-form-action dth-com-form-action--primary']),
            $this->getCancelFormAction()
                ->label(UiText::get('common.actions.cancel', 'Cancel'))
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-com-form-action dth-com-form-action--secondary']),
        ];
    }
}
