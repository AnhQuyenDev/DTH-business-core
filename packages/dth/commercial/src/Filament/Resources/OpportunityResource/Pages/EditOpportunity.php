<?php

namespace Dth\Commercial\Filament\Resources\OpportunityResource\Pages;

use Dth\Commercial\Filament\Resources\OpportunityResource;
use Dth\Commercial\Support\PageHeading;
use Dth\Commercial\Support\UiText;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditOpportunity extends EditRecord
{
    protected static string $resource = OpportunityResource::class;

    public function getTitle(): string|Htmlable
    {
        return PageHeading::make(UiText::get('pages.opportunities.edit_title', 'Edit business opportunity'), OpportunityResource::NAVIGATION_ICON);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.opportunities.edit_subheading', 'Update customer context and line items while stage changes remain controlled by the pipeline workflow.');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['currency'] = strtoupper((string) ($data['currency'] ?? 'VND'));
        $data['updated_by'] = auth()->id();
        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()->label(UiText::get('actions.save_changes', 'Save changes'))->icon('heroicon-o-check-circle')->color('primary')->extraAttributes(['class' => 'dth-com-form-action dth-com-form-action--primary']),
            $this->getCancelFormAction()->label(UiText::get('common.actions.cancel', 'Cancel'))->icon('heroicon-o-x-mark')->color('gray')->extraAttributes(['class' => 'dth-com-form-action dth-com-form-action--secondary']),
        ];
    }
}
