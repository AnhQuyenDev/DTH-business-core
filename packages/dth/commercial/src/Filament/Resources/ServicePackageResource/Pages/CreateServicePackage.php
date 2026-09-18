<?php

namespace Dth\Commercial\Filament\Resources\ServicePackageResource\Pages;

use Dth\Commercial\Filament\Resources\ServicePackageResource;
use Dth\Commercial\Support\UiText;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateServicePackage extends CreateRecord
{
    protected static string $resource = ServicePackageResource::class;

    public function getTitle(): string|Htmlable
    {
        return UiText::get('pages.packages.create_title', 'Create service package');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.packages.create_subheading', 'Package a service into a clear, reusable commercial offer for the right audience.');
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
                ->label(UiText::get('actions.save_package', 'Save package'))
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
