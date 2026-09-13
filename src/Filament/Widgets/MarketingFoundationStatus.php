<?php

namespace Dth\Marketing\Filament\Widgets;

use Dth\Marketing\Support\IntegrationHealthService;
use Dth\Marketing\Support\UiText;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MarketingFoundationStatus extends StatsOverviewWidget
{
    protected static bool $isLazy = false;
    protected ?string $pollingInterval = null;
    protected int|string|array $columnSpan = 'full';
    protected int|array|null $columns = [
        'md' => 2,
        'xl' => 3,
    ];

    protected function getHeading(): ?string
    {
        return UiText::get('overview.foundation.heading', 'Module foundation');
    }

    protected function getDescription(): ?string
    {
        return UiText::get(
            'overview.foundation.description',
            'External capabilities remain unavailable until an adapter is registered.',
        );
    }

    protected function getStats(): array
    {
        $health = app(IntegrationHealthService::class)->snapshot();

        return [
            Stat::make(
                UiText::get('overview.foundation.package', 'Package'),
                UiText::get('overview.foundation.ready', 'Ready'),
            )
                ->icon('heroicon-o-check-badge')
                ->color('success'),

            $this->integrationStat(
                UiText::get('overview.integrations.crm', 'CRM lead/contact'),
                $health['audience']['available'] && $health['lead']['available'],
                'heroicon-o-user-group',
            ),

            $this->integrationStat(
                UiText::get('overview.integrations.sales', 'Sales catalog'),
                $health['catalog']['available'],
                'heroicon-o-rectangle-stack',
            ),

            $this->integrationStat(
                UiText::get('overview.integrations.finance', 'Finance revenue'),
                $health['revenue']['available'],
                'heroicon-o-banknotes',
            ),

            $this->integrationStat(
                UiText::get('overview.integrations.email', 'Email bridge'),
                $health['email']['available'],
                'heroicon-o-envelope',
            ),
        ];
    }

    private function integrationStat(string $label, bool $available, string $icon): Stat
    {
        return Stat::make(
            $label,
            $available
                ? UiText::get('overview.integrations.available', 'Available')
                : 'N/A',
        )
            ->icon($icon)
            ->description($available
                ? UiText::get('overview.integrations.available_description', 'Adapter registered')
                : UiText::get('overview.integrations.unavailable_description', 'Adapter not registered'))
            ->color($available ? 'success' : 'gray');
    }
}
