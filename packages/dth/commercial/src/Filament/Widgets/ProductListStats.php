<?php

namespace Dth\Commercial\Filament\Widgets;

use Dth\Commercial\Models\Product;
use Dth\Commercial\Models\ProductPrice;
use Dth\Commercial\Models\Service;
use Dth\Commercial\Support\UiText;
use Filament\Widgets\Widget;

final class ProductListStats extends Widget
{
    protected static bool $isLazy = false;
    protected string $view = 'dth-commercial::filament.widgets.commercial-list-stats';
    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $total = Product::query()->count();
        $active = Product::query()->where('status', 'active')->count();
        $priced = Product::query()->whereHas('prices', fn ($q) => $q->where('status', 'active')->whereNotNull('price'))->count();
        $services = Service::query()->whereHas('products')->count();
        $prices = ProductPrice::query()->where('status', 'active')->count();

        return ['stats' => [
            [
                'label' => UiText::get('stats.total_products', 'Total products'),
                'value' => number_format($total, 0, ',', '.'),
                'meta' => UiText::get('stats.product_price_entries', ':count active price entries', ['count' => number_format($prices, 0, ',', '.')]),
                'icon' => 'heroicon-o-cube',
                'tone' => 'teal',
            ],
            [
                'label' => UiText::get('stats.active_products', 'Active products'),
                'value' => number_format($active, 0, ',', '.'),
                'meta' => UiText::get('stats.ready_to_sell', 'Available as individual opportunity line items'),
                'icon' => 'heroicon-o-check-badge',
                'tone' => 'green',
            ],
            [
                'label' => UiText::get('stats.priced_products', 'Products with price'),
                'value' => number_format($priced, 0, ',', '.'),
                'meta' => UiText::get('stats.price_book_ready', 'Have at least one active numeric price'),
                'icon' => 'heroicon-o-banknotes',
                'tone' => 'blue',
            ],
            [
                'label' => UiText::get('stats.services_with_products', 'Services with products'),
                'value' => number_format($services, 0, ',', '.'),
                'meta' => UiText::get('stats.catalog_connected', 'Connected to the service catalog'),
                'icon' => 'heroicon-o-link',
                'tone' => 'slate',
            ],
        ]];
    }
}
