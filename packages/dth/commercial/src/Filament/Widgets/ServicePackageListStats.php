<?php

namespace Dth\Commercial\Filament\Widgets;

use Dth\Commercial\Models\Service;
use Dth\Commercial\Models\ServicePackage;
use Dth\Commercial\Support\UiText;
use Filament\Widgets\Widget;

final class ServicePackageListStats extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'dth-commercial::filament.widgets.commercial-list-stats';

    protected int|string|array $columnSpan = 'full';

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $total = ServicePackage::query()->count();
        $active = ServicePackage::query()->where('status', 'active')->count();
        $business = ServicePackage::query()->whereIn('audience_type', ['business', 'both'])->count();
        $servicesWithPackages = Service::query()->whereHas('packages')->count();

        return [
            'stats' => [
                [
                    'label' => UiText::get('stats.total_packages', 'Total packages'),
                    'value' => number_format($total, 0, ',', '.'),
                    'meta' => UiText::get('stats.offer_variants', 'Offer variants ready to use'),
                    'icon' => 'heroicon-o-squares-2x2',
                    'tone' => 'teal',
                ],
                [
                    'label' => UiText::get('stats.active_packages', 'Active packages'),
                    'value' => number_format($active, 0, ',', '.'),
                    'meta' => UiText::get('stats.ready_to_offer', 'Ready to offer'),
                    'icon' => 'heroicon-o-check-badge',
                    'tone' => 'green',
                ],
                [
                    'label' => UiText::get('stats.business_packages', 'Business packages'),
                    'value' => number_format($business, 0, ',', '.'),
                    'meta' => UiText::get('stats.for_business_customers', 'For business customers'),
                    'icon' => 'heroicon-o-building-office-2',
                    'tone' => 'blue',
                ],
                [
                    'label' => UiText::get('stats.services_with_packages', 'Services with packages'),
                    'value' => number_format($servicesWithPackages, 0, ',', '.'),
                    'meta' => UiText::get('stats.catalog_connected', 'Connected to the service catalog'),
                    'icon' => 'heroicon-o-link',
                    'tone' => 'slate',
                ],
            ],
        ];
    }
}
