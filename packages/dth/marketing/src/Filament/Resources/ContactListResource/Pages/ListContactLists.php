<?php

namespace Dth\Marketing\Filament\Resources\ContactListResource\Pages;

use Dth\Marketing\Filament\Resources\ContactListResource;
use Dth\Marketing\Filament\Support\MarketingPageUi;
use Dth\Marketing\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListContactLists extends ListRecords
{
    protected static string $resource = ContactListResource::class;

    public function getTitle(): string|Htmlable
    {
        return MarketingPageUi::title(UiText::get('pages.contact_lists.title', 'Marketing Lists'), 'audience', 'green');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.contact_lists.subheading', 'Organize subscribed contacts into reusable audiences for campaigns and automation.');
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label(UiText::get('pages.contact_lists.create', 'Create list'))->icon('heroicon-o-plus')];
    }
}
