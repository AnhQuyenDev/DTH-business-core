<?php

namespace Dth\Commercial\Filament\Resources\ProductResource\Pages;

use Dth\Commercial\Filament\Resources\ProductResource;
use Dth\Commercial\Filament\Support\CommercialDataActions;
use Dth\Commercial\Filament\Widgets\ProductListStats;
use Dth\Commercial\Support\PageHeading;
use Dth\Commercial\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    public function getTitle(): string|Htmlable
    {
        return PageHeading::make(UiText::get('pages.products.title', 'Products'), ProductResource::NAVIGATION_ICON);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.products.subheading', 'Manage individual sellable products and their price lists. Products from different services can be combined in the same opportunity or bundle.');
    }

    protected function getHeaderWidgets(): array
    {
        return [ProductListStats::class];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    protected function getHeaderActions(): array
    {
        return [
            CommercialDataActions::import('products', 'manage-catalog'),
            CreateAction::make()
                ->label(UiText::get('actions.create_product', 'Create product'))
                ->icon('heroicon-o-plus')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-com-entry-action dth-com-entry-action--teal']),
        ];
    }
}
