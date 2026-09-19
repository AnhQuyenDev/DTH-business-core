<?php

namespace Dth\Commercial\Filament\Resources\ServiceResource\Pages;

use Dth\Commercial\Filament\Resources\ServiceResource;
use Dth\Commercial\Support\PageHeading;
use Dth\Commercial\Support\UiText;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

    public function getTitle(): string|Htmlable
    {
        return PageHeading::make(UiText::get('pages.services.edit_title', 'Edit service'), ServiceResource::NAVIGATION_ICON);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.services.edit_subheading', 'Keep service information current so every connected module uses the same offer context.');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label(UiText::get('actions.save_changes', 'Save changes'))
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
