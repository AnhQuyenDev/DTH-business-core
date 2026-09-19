<?php

namespace Dth\Commercial\Filament\Widgets;

use Dth\Commercial\Models\Bundle;
use Dth\Commercial\Models\Product;
use Dth\Commercial\Support\UiText;
use Filament\Widgets\Widget;

final class BundleListStats extends Widget
{
    protected static bool $isLazy = false;
    protected string $view = 'dth-commercial::filament.widgets.commercial-list-stats';
    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $total = Bundle::query()->count();
        $active = Bundle::query()->where('status', 'active')->count();
        $crossService = Bundle::query()->with('items.product:id,service_id')->whereHas('items')->get()->filter(function (Bundle $bundle): bool {
            return $bundle->items->pluck('product.service_id')->filter()->unique()->count() > 1;
        })->count();
        $productsInBundles = Product::query()->whereHas('bundleItems')->count();

        return ['stats' => [
            [
                'label' => UiText::get('stats.total_packages', 'Total bundles'),
                'value' => number_format($total, 0, ',', '.'),
                'meta' => UiText::get('stats.offer_variants', 'Predefined combination offers'),
                'icon' => 'heroicon-o-gift',
                'tone' => 'teal',
            ],
            [
                'label' => UiText::get('stats.active_packages', 'Active bundles'),
                'value' => number_format($active, 0, ',', '.'),
                'meta' => UiText::get('stats.ready_to_offer', 'Ready to offer'),
                'icon' => 'heroicon-o-check-badge',
                'tone' => 'green',
            ],
            [
                'label' => UiText::get('stats.cross_service_bundles', 'Cross-service bundles'),
                'value' => number_format($crossService, 0, ',', '.'),
                'meta' => UiText::get('stats.cross_service_bundles_help', 'Combine products from more than one service'),
                'icon' => 'heroicon-o-arrows-right-left',
                'tone' => 'blue',
            ],
            [
                'label' => UiText::get('stats.products_in_bundles', 'Products used in bundles'),
                'value' => number_format($productsInBundles, 0, ',', '.'),
                'meta' => UiText::get('stats.bundle_catalog_coverage', 'Reusable products included in at least one bundle'),
                'icon' => 'heroicon-o-cube-transparent',
                'tone' => 'slate',
            ],
        ]];
    }
}
