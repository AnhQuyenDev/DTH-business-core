<?php

namespace Dth\Marketing\Filament\Resources\ContactListResource\Pages;

use Dth\Marketing\Filament\Resources\ContactListResource;
use Dth\Marketing\Filament\Support\MarketingPageUi;
use Dth\Marketing\Support\UiText;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditContactList extends EditRecord
{
    protected static string $resource = ContactListResource::class;

    public function getTitle(): string|Htmlable
    {
        return MarketingPageUi::title(UiText::get('pages.contact_list_edit.title', 'Edit Marketing List'), 'audience', 'green');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.contact_list_edit.subheading', 'Update audience details while keeping subscription and member history intact.');
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->label(UiText::get('common.actions.delete', 'Delete'))];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()->label(UiText::get('common.actions.save', 'Save'))->icon('heroicon-o-check-circle')
                ->color('primary')
                ->extraAttributes(['class' => 'dth-mkt-form-action dth-mkt-form-action--primary']),
            $this->getCancelFormAction()->label(UiText::get('common.actions.cancel', 'Cancel'))->icon('heroicon-o-x-mark')->color('gray')
                ->extraAttributes(['class' => 'dth-mkt-form-action dth-mkt-form-action--secondary']),
        ];
    }
}
