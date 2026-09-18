<?php

namespace Dth\Marketing\Filament\Resources\ContactListResource\Pages;

use Dth\Marketing\Filament\Resources\ContactListResource;
use Dth\Marketing\Filament\Support\MarketingPageUi;
use Dth\Marketing\Support\UiText;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateContactList extends CreateRecord
{
    protected static string $resource = ContactListResource::class;

    public function getTitle(): string|Htmlable
    {
        return MarketingPageUi::title(UiText::get('pages.contact_list_create.title', 'Create Marketing List'), 'audience', 'green');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.contact_list_create.subheading', 'Create a reusable marketing audience and define its type and subscription purpose.');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label(UiText::get('common.actions.save', 'Save'))->icon('heroicon-o-check-circle')
                ->color('primary')
                ->extraAttributes(['class' => 'dth-mkt-form-action dth-mkt-form-action--primary']),
            $this->getCancelFormAction()->label(UiText::get('common.actions.cancel', 'Cancel'))->icon('heroicon-o-x-mark')->color('gray')
                ->extraAttributes(['class' => 'dth-mkt-form-action dth-mkt-form-action--secondary']),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }
}
