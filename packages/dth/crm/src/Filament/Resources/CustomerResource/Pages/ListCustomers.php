<?php

namespace Dth\Crm\Filament\Resources\CustomerResource\Pages;

use Dth\Crm\Filament\Resources\CustomerResource;
use Dth\Crm\Filament\Support\CrmDataActions;
use Dth\Crm\Filament\Support\CrmPageUi;
use Dth\Crm\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    public function getTitle(): string|Htmlable
    {
        return CrmPageUi::title(UiText::get('pages.customers.title', 'Customers'), 'customer');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.customers.subheading', 'Manage customer lifecycle, priority, revenue, ownership, and customer-care history.');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(UiText::get('pages.customers.create', 'Add customer'))
                ->icon('heroicon-o-user-plus')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-crm-entry-action dth-crm-entry-action--teal']),
            ...CrmDataActions::make('customers', fn () => $this->getFilteredTableQuery(), 'crm-customers', 'Customers'),
        ];
    }
}
