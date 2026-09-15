<?php

namespace Dth\Marketing\Filament\Resources\SegmentResource\Pages;

use Dth\Marketing\Filament\Resources\SegmentResource;
use Dth\Marketing\Filament\Support\MarketingPageUi;
use Dth\Marketing\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListSegments extends ListRecords
{
    protected static string $resource = SegmentResource::class;

    public function getTitle(): string|Htmlable
    {
        return MarketingPageUi::title(UiText::get('pages.segments.title', 'Segments'), 'segment', 'rose');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.segments.subheading', 'Build rule-based audience segments and preview matching contacts before activation.');
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label(UiText::get('pages.segments.create', 'Create segment'))->icon('heroicon-o-plus')];
    }
}
