<?php

namespace Dth\Marketing\Filament\Resources\SegmentResource\Pages;

use Dth\Marketing\Filament\Resources\SegmentResource;
use Dth\Marketing\Filament\Support\MarketingPageUi;
use Dth\Marketing\Support\UiText;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateSegment extends CreateRecord
{
    protected static string $resource = SegmentResource::class;

    public function getTitle(): string|Htmlable
    {
        return MarketingPageUi::title(UiText::get('pages.segment_create.title', 'Create Segment'), 'segment', 'rose');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.segment_create.subheading', 'Combine supported rules to define a focused marketing audience.');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label(UiText::get('common.actions.save', 'Save'))->icon('heroicon-o-check'),
            $this->getCancelFormAction()->label(UiText::get('common.actions.cancel', 'Cancel'))->icon('heroicon-o-x-mark')->color('gray'),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['is_automatic'] = false;
        return $data;
    }
}
