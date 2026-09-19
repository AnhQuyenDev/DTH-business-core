<?php

namespace Dth\Commercial\Filament\Resources\ServicePackageResource\Pages;

use Dth\Commercial\Filament\Resources\ServicePackageResource;
use Dth\Commercial\Support\PageHeading;
use Dth\Commercial\Support\UiText;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditServicePackage extends EditRecord
{
    protected static string $resource = ServicePackageResource::class;

    public function getTitle(): string|Htmlable
    {
        return PageHeading::make(UiText::get('pages.packages.edit_title', 'Edit service package'), ServicePackageResource::NAVIGATION_ICON);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.packages.edit_subheading', 'Maintain package targeting, billing rules and positioning without changing historical snapshots.');
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
