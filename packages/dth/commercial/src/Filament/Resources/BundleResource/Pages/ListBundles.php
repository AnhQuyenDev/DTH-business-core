<?php

namespace Dth\Commercial\Filament\Resources\BundleResource\Pages;

use Dth\Commercial\Filament\Resources\BundleResource;
use Dth\Commercial\Filament\Support\CommercialDataActions;
use Dth\Commercial\Filament\Widgets\BundleListStats;
use Dth\Commercial\Support\PageHeading;
use Dth\Commercial\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListBundles extends ListRecords
{
    protected static string $resource = BundleResource::class;

    public function getTitle(): string|Htmlable
    {
        return PageHeading::make(UiText::get('pages.packages.title', 'Service bundles'), BundleResource::NAVIGATION_ICON);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.packages.subheading', 'Build reusable bundles from products across one or many services. Customers can still buy each product separately.');
    }

    protected function getHeaderWidgets(): array
    {
        return [BundleListStats::class];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    protected function getHeaderActions(): array
    {
        return [
            CommercialDataActions::import('bundles', 'manage-catalog'),
            CreateAction::make()
                ->label(UiText::get('actions.create_package', 'Create bundle'))
                ->icon('heroicon-o-plus')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-com-entry-action dth-com-entry-action--teal']),
        ];
    }
}
