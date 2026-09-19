<?php

namespace Dth\Commercial\Filament\Widgets;

use Dth\Commercial\Models\Bundle;
use Dth\Commercial\Models\Product;
use Dth\Commercial\Models\Service;
use Dth\Commercial\Support\UiText;
use Filament\Widgets\Widget;

final class ServiceListStats extends Widget
{
    protected static bool $isLazy = false;
    protected string $view = 'dth-commercial::filament.widgets.commercial-list-stats';
    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $total = Service::query()->count();
        $active = Service::query()->where('status', 'active')->count();
        $products = Product::query()->count();
        $bundles = Bundle::query()->count();

        return ['stats' => [
            [
                'label' => UiText::get('stats.total_services', 'Total services'),
                'value' => number_format($total, 0, ',', '.'),
                'meta' => UiText::get('stats.catalog_product_coverage', ':products products · :bundles bundles', [
                    'products' => number_format($products, 0, ',', '.'),
                    'bundles' => number_format($bundles, 0, ',', '.'),
                ]),
                'icon' => 'heroicon-o-rectangle-stack',
                'tone' => 'teal',
            ],
            [
                'label' => UiText::get('stats.active_services', 'Active'),
                'value' => number_format($active, 0, ',', '.'),
                'meta' => UiText::get('stats.available_for_campaigns', 'Available for catalog and campaigns'),
                'icon' => 'heroicon-o-check-circle',
                'tone' => 'green',
            ],
            [
                'label' => UiText::get('stats.services_with_products', 'Services with products'),
                'value' => number_format(Service::query()->whereHas('products')->count(), 0, ',', '.'),
                'meta' => UiText::get('stats.ready_to_sell', 'Contain at least one sellable product'),
                'icon' => 'heroicon-o-cube',
                'tone' => 'blue',
            ],
            [
                'label' => UiText::get('stats.empty_services', 'Services without products'),
                'value' => number_format(Service::query()->whereDoesntHave('products')->count(), 0, ',', '.'),
                'meta' => UiText::get('stats.empty_services_help', 'Need product definitions before direct selling'),
                'icon' => 'heroicon-o-exclamation-triangle',
                'tone' => 'amber',
            ],
        ]];
    }
}
