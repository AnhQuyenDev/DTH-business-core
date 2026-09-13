<?php

namespace Dth\Marketing\Filament\Pages;

use Dth\Marketing\Filament\Navigation\MarketingNavigationGroup;
use Dth\Marketing\Filament\Widgets\MarketingFoundationStatus;
use Dth\Marketing\Support\UiText;
use Filament\Pages\Dashboard;
use Illuminate\Contracts\Support\Htmlable;

class MarketingOverview extends Dashboard
{
    protected static string $routePath = 'marketing-dashboard';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string|\UnitEnum|null $navigationGroup = MarketingNavigationGroup::Marketing;
    protected static ?int $navigationSort = 0;

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.dashboard', 'Marketing overview', context: 'navigation');
    }

    public function getTitle(): string|Htmlable
    {
        return UiText::get('overview.title', 'Marketing');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get(
            'overview.subheading',
            'M-B through M-D are enabled: Campaigns, Form Templates and Landing Pages. Public submission starts in M-E after this UAT baseline passes.',
        );
    }

    /** @return array<class-string<\Filament\Widgets\Widget>> */
    public function getWidgets(): array
    {
        return [
            MarketingFoundationStatus::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}
