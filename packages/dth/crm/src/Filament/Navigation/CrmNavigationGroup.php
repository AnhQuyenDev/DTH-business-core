<?php

namespace Dth\Crm\Filament\Navigation;

use BackedEnum;
use Dth\Crm\Support\UiText;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum CrmNavigationGroup implements HasLabel, HasIcon
{
	case Crm;

	public function getLabel(): string
	{
		return UiText::get('navigation.group', 'CRM', context: 'navigation');
	}

	public function getIcon(): string|BackedEnum|Htmlable|null
	{
		return Heroicon::OutlinedUsers;
	}
}
