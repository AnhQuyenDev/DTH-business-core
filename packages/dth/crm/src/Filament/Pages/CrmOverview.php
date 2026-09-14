<?php

namespace Dth\Crm\Filament\Pages;

use Dth\Crm\Filament\Navigation\CrmNavigationGroup;
use Dth\Crm\Filament\Resources\ContactResource;
use Dth\Crm\Filament\Resources\LeadResource;
use Dth\Crm\Services\CrmAnalyticsService;
use Dth\Crm\Support\CrmAuthorization;
use Dth\Crm\Support\UiText;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class CrmOverview extends Page
{
    protected string $view = 'dth-crm::filament.pages.crm-overview';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string|\UnitEnum|null $navigationGroup = CrmNavigationGroup::Crm;
    protected static ?int $navigationSort = 0;

    /** @var array<string, mixed> */
    public array $snapshot = [];

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.dashboard', 'CRM overview', context: 'navigation');
    }

    public function getTitle(): string|Htmlable
    {
        return UiText::get('dashboard.title', 'CRM Analytics');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get(
            'dashboard.subheading',
            'Track contacts, leads, qualification, conversion, customers and team workload in one place.',
        );
    }

    public function mount(): void
    {
        $this->snapshot = app(CrmAnalyticsService::class)->snapshot();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newContact')
                ->label(UiText::get('actions.new_contact', 'New contact'))
                ->icon('heroicon-o-user-plus')
                ->color('gray')
                ->url(fn (): string => ContactResource::getUrl('create'))
                ->visible(fn (): bool => ContactResource::canCreate()),
            Action::make('newLead')
                ->label(UiText::get('actions.new_lead', 'New Lead'))
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(fn (): string => LeadResource::getUrl('create'))
                ->visible(fn (): bool => LeadResource::canCreate()),
        ];
    }

    public static function canAccess(): bool
    {
        return app(CrmAuthorization::class)->allows('crm.view');
    }
}
