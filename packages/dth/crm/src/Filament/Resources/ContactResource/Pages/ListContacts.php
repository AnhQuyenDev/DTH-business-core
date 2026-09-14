<?php

namespace Dth\Crm\Filament\Resources\ContactResource\Pages;

use Dth\Crm\Filament\Resources\ContactResource;
use Dth\Crm\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListContacts extends ListRecords
{
	protected static string $resource = ContactResource::class;

	protected function getHeaderActions(): array
	{
		return [
			CreateAction::make()->label(UiText::get('common.actions.add', 'Add')),
		];
	}

	public function getTabs(): array
	{
		return [
			'personal' => Tab::make(UiText::get('navigation.personal_contacts', 'Personal contacts'))
				->modifyQueryUsing(fn ($query) => $query->where('type', 'personal')),
			'business' => Tab::make(UiText::get('navigation.business_contacts', 'Business contacts'))
				->modifyQueryUsing(fn ($query) => $query->where('type', 'business')),
		];
	}
}
