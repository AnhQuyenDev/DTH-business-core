<?php

namespace Dth\Commercial\Filament\Resources\OpportunityResource\Pages;

use Dth\Commercial\Enums\OpportunityStage;
use Dth\Commercial\Filament\Resources\OpportunityResource;
use Dth\Commercial\Services\OpportunityCodeGenerator;
use Dth\Commercial\Support\PageHeading;
use Dth\Commercial\Support\UiText;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateOpportunity extends CreateRecord
{
    protected static string $resource = OpportunityResource::class;

    public function getTitle(): string|Htmlable
    {
        return PageHeading::make(UiText::get('pages.opportunities.create_title', 'Create business opportunity'), OpportunityResource::NAVIGATION_ICON);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.opportunities.create_subheading', 'Capture the customer context, then add any mix of products and bundles required for the deal.');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $stage = OpportunityStage::tryFrom((string) ($data['stage'] ?? '')) ?? OpportunityStage::Discovery;
        $data['opportunity_code'] = app(OpportunityCodeGenerator::class)->next();
        $data['probability'] = $data['probability'] ?? $stage->probability();
        $data['currency'] = strtoupper((string) ($data['currency'] ?? 'VND'));
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label(UiText::get('actions.save_opportunity', 'Save opportunity'))->icon('heroicon-o-check-circle')->color('primary')->extraAttributes(['class' => 'dth-com-form-action dth-com-form-action--primary']),
            $this->getCancelFormAction()->label(UiText::get('common.actions.cancel', 'Cancel'))->icon('heroicon-o-x-mark')->color('gray')->extraAttributes(['class' => 'dth-com-form-action dth-com-form-action--secondary']),
        ];
    }
}
