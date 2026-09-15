<?php

namespace Dth\Marketing\Filament\Resources\SegmentResource\Pages;

use Dth\Marketing\Filament\Resources\SegmentResource;
use Dth\Marketing\Filament\Support\MarketingPageUi;
use Dth\Marketing\Support\UiText;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditSegment extends EditRecord
{
    protected static string $resource = SegmentResource::class;

    public function getTitle(): string|Htmlable
    {
        return MarketingPageUi::title(UiText::get('pages.segment_edit.title', 'Edit Segment'), 'segment', 'rose');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.segment_edit.subheading', 'Refine audience conditions and evaluate matching contacts before using the segment.');
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->label(UiText::get('common.actions.delete', 'Delete'))];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()->label(UiText::get('common.actions.save', 'Save'))->icon('heroicon-o-check'),
            $this->getCancelFormAction()->label(UiText::get('common.actions.cancel', 'Cancel'))->icon('heroicon-o-x-mark')->color('gray'),
        ];
    }
}
