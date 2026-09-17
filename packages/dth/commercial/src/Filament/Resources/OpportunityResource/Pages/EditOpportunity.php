<?php

namespace Dth\Commercial\Filament\Resources\OpportunityResource\Pages;

use Dth\Commercial\Filament\Resources\OpportunityResource;
use Dth\Commercial\Models\Service;
use Dth\Commercial\Support\UiText;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditOpportunity extends EditRecord
{
    protected static string $resource = OpportunityResource::class;

    public function getTitle(): string|Htmlable
    {
        return UiText::get('pages.opportunities.edit_title', 'Edit business opportunity');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.opportunities.edit_subheading', 'Keep customer context, value and timing accurate while stage changes remain controlled by the pipeline workflow.');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $service = filled($data['service_id'] ?? null) ? Service::query()->find($data['service_id']) : null;

        if ($service) {
            $data['service_reference'] = $service->reference();
            $data['service_name_snapshot'] = $service->name;
        }

        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label(UiText::get('actions.save_changes', 'Save changes'))
                ->icon('heroicon-o-check'),
            $this->getCancelFormAction()
                ->label(UiText::get('common.actions.cancel', 'Cancel'))
                ->icon('heroicon-o-x-mark')
                ->color('gray'),
        ];
    }
}
