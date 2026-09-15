<?php

namespace Dth\HumanResource\Filament\Pages;

use Dth\HumanResource\Filament\Navigation\HumanResourceNavigationGroup;
use Dth\HumanResource\Filament\Resources\DepartmentResource;
use Dth\HumanResource\Filament\Resources\EmployeeResource;
use Dth\HumanResource\Filament\Resources\PositionResource;
use Dth\HumanResource\Services\HumanResourceAnalyticsService;
use Dth\HumanResource\Support\HumanResourceAuthorization;
use Dth\HumanResource\Support\UiText;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class HumanResourceOverview extends Page
{
    protected string $view = 'dth-human-resource::filament.pages.human-resource-overview';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string|\UnitEnum|null $navigationGroup = HumanResourceNavigationGroup::HumanResource;
    protected static ?int $navigationSort = 0;

    /** @var array<string, mixed> */
    public array $snapshot = [];

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.dashboard', 'HR overview', context: 'navigation');
    }

    public function getTitle(): string|Htmlable
    {
        return UiText::get('dashboard.title', 'Human Resource');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('dashboard.subheading', 'Employee master data, organization structure and workforce availability.');
    }

    public function mount(): void
    {
        $this->snapshot = app(HumanResourceAnalyticsService::class)->snapshot();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newEmployee')
                ->label(UiText::get('actions.new_employee', 'New employee'))
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->url(fn (): string => EmployeeResource::getUrl('create'))
                ->visible(fn (): bool => EmployeeResource::canCreate()),
            Action::make('departments')
                ->label(UiText::get('navigation.departments', 'Departments'))
                ->icon('heroicon-o-building-office-2')
                ->color('gray')
                ->url(fn (): string => DepartmentResource::getUrl('index')),
            Action::make('positions')
                ->label(UiText::get('navigation.positions', 'Job titles'))
                ->icon('heroicon-o-briefcase')
                ->color('gray')
                ->url(fn (): string => PositionResource::getUrl('index')),
        ];
    }

    public static function canAccess(): bool
    {
        return app(HumanResourceAuthorization::class)->allows('hr.view');
    }
}
