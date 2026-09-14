<?php

namespace Dth\Crm\Filament\Pages;

use Dth\Crm\Filament\Navigation\CrmNavigationGroup;
use Dth\Crm\Services\CrmAnalyticsService;
use Dth\Crm\Support\UiText;
use Filament\Pages\Page;

class CrmOverview extends Page
{
	protected string $view = 'dth-crm::filament.pages.crm-overview';
	protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';
	protected static string|\UnitEnum|null $navigationGroup = CrmNavigationGroup::Crm;
	protected static ?string $navigationLabel = null;
	protected static ?int $navigationSort = 1;
	public array $snapshot = [];

	public static function getNavigationLabel(): string
	{
		return UiText::get('navigation.dashboard', 'CRM overview', context: 'navigation');
	}

	public function mount(): void
	{
		$this->snapshot = app(CrmAnalyticsService::class)->snapshot();
	}

	public static function canAccess(): bool
	{
		return app(\Dth\Crm\Support\CrmAuthorization::class)->allows('crm.view');
	}
}
