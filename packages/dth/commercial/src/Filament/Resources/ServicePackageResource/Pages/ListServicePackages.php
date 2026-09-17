<?php

namespace Dth\Commercial\Filament\Resources\ServicePackageResource\Pages;

use Dth\Commercial\Filament\Resources\ServicePackageResource;
use Dth\Commercial\Filament\Widgets\ServicePackageListStats;
use Dth\Commercial\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListServicePackages extends ListRecords
{
    protected static string $resource = ServicePackageResource::class;

    public function getTitle(): string|Htmlable
    {
        return UiText::get('pages.packages.title', 'Service packages');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.packages.subheading', 'Build reusable commercial packages around the service catalog for consistent offers across channels.');
    }

    /** @return array<class-string<\Filament\Widgets\Widget>> */
    protected function getHeaderWidgets(): array
    {
        return [ServicePackageListStats::class];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(UiText::get('actions.create_package', 'Create package'))
                ->icon('heroicon-o-plus')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-com-entry-action dth-com-entry-action--teal']),
        ];
    }
}
