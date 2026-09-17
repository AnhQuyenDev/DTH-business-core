<?php

namespace Dth\Commercial\Filament\Widgets;

use Dth\Commercial\Models\Service;
use Dth\Commercial\Models\ServicePackage;
use Dth\Commercial\Support\UiText;
use Filament\Widgets\Widget;

final class ServiceListStats extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'dth-commercial::filament.widgets.commercial-list-stats';

    protected int|string|array $columnSpan = 'full';

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $total = Service::query()->count();
        $active = Service::query()->where('status', 'active')->count();
        $inactive = Service::query()->where('status', 'inactive')->count();
        $archived = Service::query()->where('status', 'archived')->count();
        $packages = ServicePackage::query()->count();

        return [
            'stats' => [
                [
                    'label' => UiText::get('stats.total_services', 'Total services'),
                    'value' => number_format($total, 0, ',', '.'),
                    'meta' => UiText::get('stats.catalog_coverage', ':count packages in catalog', ['count' => number_format($packages, 0, ',', '.')]),
                    'icon' => 'heroicon-o-cube',
                    'tone' => 'teal',
                ],
                [
                    'label' => UiText::get('stats.active_services', 'Active'),
                    'value' => number_format($active, 0, ',', '.'),
                    'meta' => UiText::get('stats.available_for_campaigns', 'Available for campaigns'),
                    'icon' => 'heroicon-o-check-circle',
                    'tone' => 'green',
                ],
                [
                    'label' => UiText::get('stats.inactive_services', 'Inactive'),
                    'value' => number_format($inactive, 0, ',', '.'),
                    'meta' => UiText::get('stats.temporarily_unavailable', 'Temporarily unavailable'),
                    'icon' => 'heroicon-o-pause-circle',
                    'tone' => 'amber',
                ],
                [
                    'label' => UiText::get('stats.archived_services', 'Archived'),
                    'value' => number_format($archived, 0, ',', '.'),
                    'meta' => UiText::get('stats.kept_for_history', 'Kept for historical reference'),
                    'icon' => 'heroicon-o-archive-box',
                    'tone' => 'slate',
                ],
            ],
        ];
    }
}
