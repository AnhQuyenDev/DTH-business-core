<?php

namespace Dth\Marketing\Filament\Resources\LandingPageResource\Pages;

use Dth\Marketing\Filament\Resources\LandingPageResource;
use Dth\Marketing\Filament\Support\MarketingPageUi;
use Dth\Marketing\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListLandingPages extends ListRecords
{
    protected static string $resource = LandingPageResource::class;

    public function getTitle(): string|Htmlable
    {
        return MarketingPageUi::title(UiText::get('pages.landing_pages.title', 'Landing Pages'), 'landing', 'blue');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.landing_pages.subheading', 'Build, publish, and track landing pages connected to campaigns, forms, and UTM attribution.');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(UiText::get('pages.landing_pages.create', 'Create landing page'))
                ->icon('heroicon-o-plus')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-mkt-entry-action dth-mkt-entry-action--blue']),
        ];
    }
}
