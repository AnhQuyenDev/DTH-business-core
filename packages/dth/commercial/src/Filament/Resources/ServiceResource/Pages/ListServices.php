<?php

namespace Dth\Commercial\Filament\Resources\ServiceResource\Pages;

use Dth\Commercial\Filament\Resources\ServiceResource;
use Dth\Commercial\Filament\Support\CommercialDataActions;
use Dth\Commercial\Filament\Widgets\ServiceListStats;
use Dth\Commercial\Support\PageHeading;
use Dth\Commercial\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListServices extends ListRecords
{
    protected static string $resource = ServiceResource::class;

    public function getTitle(): string|Htmlable
    {
        return PageHeading::make(UiText::get('pages.services.title', 'Service catalog'), ServiceResource::NAVIGATION_ICON);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.services.subheading', 'Manage the canonical services offered to customers and reused by Marketing and Email campaigns.');
    }

    /** @return array<class-string<\Filament\Widgets\Widget>> */
    protected function getHeaderWidgets(): array
    {
        return [ServiceListStats::class];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    protected function getHeaderActions(): array
    {
        return [
            CommercialDataActions::import('services', 'manage-catalog'),
            CreateAction::make()
                ->label(UiText::get('actions.create_service', 'Create service'))
                ->icon('heroicon-o-plus')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-com-entry-action dth-com-entry-action--teal']),
        ];
    }
}
