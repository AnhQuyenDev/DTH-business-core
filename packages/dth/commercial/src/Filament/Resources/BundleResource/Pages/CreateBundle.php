<?php

namespace Dth\Commercial\Filament\Resources\BundleResource\Pages;

use Dth\Commercial\Filament\Resources\BundleResource;
use Dth\Commercial\Support\PageHeading;
use Dth\Commercial\Support\UiText;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateBundle extends CreateRecord
{
    protected static string $resource = BundleResource::class;

    public function getTitle(): string|Htmlable
    {
        return PageHeading::make(UiText::get('pages.packages.create_title', 'Create service bundle'), BundleResource::NAVIGATION_ICON);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.packages.create_subheading', 'Combine products into a reusable commercial offer. Products may come from different services.');
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
            $this->getCreateFormAction()->label(UiText::get('actions.save_package', 'Save bundle'))->icon('heroicon-o-check-circle')->color('primary')->extraAttributes(['class' => 'dth-com-form-action dth-com-form-action--primary']),
            $this->getCancelFormAction()->label(UiText::get('common.actions.cancel', 'Cancel'))->icon('heroicon-o-x-mark')->color('gray')->extraAttributes(['class' => 'dth-com-form-action dth-com-form-action--secondary']),
        ];
    }
}
