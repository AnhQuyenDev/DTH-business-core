<?php

namespace Dth\Commercial\Filament\Resources\OpportunityResource\Pages;

use Dth\Commercial\Enums\OpportunityStage;
use Dth\Commercial\Filament\Resources\OpportunityResource;
use Dth\Commercial\Models\Service;
use Dth\Commercial\Services\OpportunityCodeGenerator;
use Dth\Commercial\Support\UiText;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateOpportunity extends CreateRecord
{
    protected static string $resource = OpportunityResource::class;

    public function getTitle(): string|Htmlable
    {
        return UiText::get('pages.opportunities.create_title', 'Create business opportunity');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.opportunities.create_subheading', 'Capture enough context to forecast, follow up and move the opportunity through the pipeline confidently.');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $service = filled($data['service_id'] ?? null) ? Service::query()->find($data['service_id']) : null;

        if ($service) {
            $data['service_reference'] = $service->reference();
            $data['service_name_snapshot'] = $service->name;
        }

        $stage = OpportunityStage::tryFrom((string) ($data['stage'] ?? '')) ?? OpportunityStage::Discovery;
        $data['opportunity_code'] = app(OpportunityCodeGenerator::class)->next();
        $data['probability'] = $data['probability'] ?? $stage->probability();
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label(UiText::get('actions.save_opportunity', 'Save opportunity'))
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
