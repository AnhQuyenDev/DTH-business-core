<?php

namespace Dth\Commercial\Integrations\Marketing;

use Dth\Commercial\Enums\ServiceStatus;
use Dth\Commercial\Models\Bundle;
use Dth\Commercial\Models\Product;
use Dth\Commercial\Models\Service;
use Dth\Marketing\Contracts\CatalogProvider;
use Dth\Marketing\DTO\CatalogPackageData;
use Dth\Marketing\DTO\CatalogServiceData;

final class DthMarketingCatalogProvider implements CatalogProvider
{
    public function available(): bool
    {
        return (bool) config('dth-commercial.enabled', true)
            && (bool) config('dth-commercial.features.catalog', true);
    }

    public function capabilities(): array
    {
        $products = $this->available() && (bool) config('dth-commercial.features.products', true);
        $bundles = $this->available() && (bool) config('dth-commercial.features.bundles', config('dth-commercial.features.packages', true));

        return [
            'services' => $this->available(),
            'products' => $products,
            'bundles' => $bundles,
            // Marketing's existing contract names the selectable offer unit
            // "package". For compatibility, Commercial exposes both standalone
            // Products and Bundles through that method and marks catalog_type in
            // metadata so newer Marketing code can distinguish them.
            'packages' => $products || $bundles,
        ];
    }

    public function services(): array
    {
        if (! $this->available()) {
            return [];
        }

        return Service::query()
            ->withCount(['products', 'bundles'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Service $service): CatalogServiceData => $this->toServiceData($service))
            ->all();
    }

    public function findService(string $reference): ?CatalogServiceData
    {
        if (! $this->available()) {
            return null;
        }

        $reference = trim($reference);
        $service = Service::query()
            ->withCount(['products', 'bundles'])
            ->where('slug', $reference)
            ->orWhere('service_code', strtoupper($reference))
            ->first();

        return $service ? $this->toServiceData($service) : null;
    }

    /**
     * Backward-compatible Marketing offer list.
     *
     * Products are first-class sellable offers, while Bundles are predefined
     * combinations. Returning both here lets the current Marketing module keep
     * using CatalogProvider without forcing customers into a Bundle.
     */
    public function packagesForService(string $serviceReference): array
    {
        if (! $this->available()) {
            return [];
        }

        $service = Service::query()
            ->where('slug', $serviceReference)
            ->orWhere('service_code', strtoupper($serviceReference))
            ->first();

        if (! $service) {
            return [];
        }

        $offers = collect();

        if ((bool) config('dth-commercial.features.products', true)) {
            Product::query()
                ->with(['service', 'prices'])
                ->where('service_id', $service->getKey())
                ->where('status', ServiceStatus::Active->value)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->each(fn (Product $product) => $offers->push($this->toProductData($product, $service)));
        }

        if ((bool) config('dth-commercial.features.bundles', config('dth-commercial.features.packages', true))) {
            Bundle::query()
                ->with(['primaryService', 'items.product.service', 'items.product.prices'])
                ->where('status', ServiceStatus::Active->value)
                ->where(function ($query) use ($service): void {
                    $query->where('primary_service_id', $service->getKey())
                        ->orWhereHas('items.product', fn ($q) => $q->where('service_id', $service->getKey()));
                })
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->each(fn (Bundle $bundle) => $offers->push($this->toBundleData($bundle, $bundle->primaryService ?: $service)));
        }

        return $offers->values()->all();
    }

    /**
     * Existing Marketing records may contain a former ServicePackage code.
     * Migration 000004 turns those rows into Products with the same code, so
     * Product lookup is attempted first to keep historical campaign links alive.
     */
    public function findPackage(string $reference): ?CatalogPackageData
    {
        if (! $this->available()) {
            return null;
        }

        $code = strtoupper(trim($reference));

        if ((bool) config('dth-commercial.features.products', true)) {
            $product = Product::query()
                ->with(['service', 'prices'])
                ->where('product_code', $code)
                ->first();

            if ($product?->service) {
                return $this->toProductData($product, $product->service);
            }
        }

        if (! config('dth-commercial.features.bundles', config('dth-commercial.features.packages', true))) {
            return null;
        }

        $bundle = Bundle::query()
            ->with(['primaryService', 'items.product.service', 'items.product.prices'])
            ->where('bundle_code', $code)
            ->first();

        if (! $bundle) {
            return null;
        }

        $service = $bundle->primaryService ?: $bundle->items->first()?->product?->service;

        return $service ? $this->toBundleData($bundle, $service) : null;
    }

    private function toServiceData(Service $service): CatalogServiceData
    {
        $status = $service->status instanceof ServiceStatus ? $service->status->value : (string) $service->status;

        return new CatalogServiceData(
            reference: $service->reference(),
            name: (string) $service->name,
            code: (string) $service->service_code,
            active: $status === ServiceStatus::Active->value,
            metadata: [
                'description' => $service->description,
                'scope' => $service->default_scope,
                'terms' => $service->default_terms,
                'products_count' => (int) ($service->products_count ?? $service->products()->count()),
                'bundles_count' => (int) ($service->bundles_count ?? $service->bundles()->count()),
                'commercial_id' => $service->getKey(),
            ],
        );
    }

    private function toProductData(Product $product, Service $service): CatalogPackageData
    {
        $status = $product->status instanceof ServiceStatus ? $product->status->value : (string) $product->status;
        $price = $product->preferredPrice();

        return new CatalogPackageData(
            reference: $product->reference(),
            serviceReference: $service->reference(),
            name: (string) $product->name,
            code: (string) $product->product_code,
            active: $status === ServiceStatus::Active->value,
            metadata: [
                'catalog_type' => 'product',
                'description' => $product->description,
                'audience_type' => $product->audience_type instanceof \BackedEnum ? $product->audience_type->value : $product->audience_type,
                'unit' => $product->unit,
                'default_quantity' => (float) $product->default_quantity,
                'billing_period' => $price?->billing_period,
                'billing_period_unit' => $price?->billing_period_unit instanceof \BackedEnum ? $price->billing_period_unit->value : $price?->billing_period_unit,
                'price' => $price?->price !== null ? (float) $price->price : null,
                'renewal_price' => $price?->renewal_price !== null ? (float) $price->renewal_price : null,
                'setup_fee' => (float) ($price?->setup_fee ?? 0),
                'currency' => $price?->currency ?: 'VND',
                'commercial_id' => $product->getKey(),
            ],
        );
    }

    private function toBundleData(Bundle $bundle, Service $service): CatalogPackageData
    {
        $status = $bundle->status instanceof ServiceStatus ? $bundle->status->value : (string) $bundle->status;

        return new CatalogPackageData(
            reference: $bundle->reference(),
            serviceReference: $service->reference(),
            name: (string) $bundle->name,
            code: (string) $bundle->bundle_code,
            active: $status === ServiceStatus::Active->value,
            metadata: [
                'catalog_type' => 'bundle',
                'description' => $bundle->description,
                'audience_type' => $bundle->audience_type instanceof \BackedEnum ? $bundle->audience_type->value : $bundle->audience_type,
                'pricing_type' => $bundle->pricing_type instanceof \BackedEnum ? $bundle->pricing_type->value : $bundle->pricing_type,
                'billing_period' => $bundle->billing_period,
                'billing_period_unit' => $bundle->billing_period_unit instanceof \BackedEnum ? $bundle->billing_period_unit->value : $bundle->billing_period_unit,
                'price' => $bundle->effectivePrice(),
                'renewal_price' => $bundle->renewal_price !== null ? (float) $bundle->renewal_price : null,
                'setup_fee' => $bundle->effectiveSetupFee(),
                'currency' => $bundle->currency ?: 'VND',
                'items' => $bundle->items->map(fn ($item): array => [
                    'product_code' => $item->product?->product_code,
                    'product_name' => $item->product?->name,
                    'service' => $item->product?->service?->name,
                    'quantity' => (float) $item->quantity,
                    'required' => (bool) $item->required,
                ])->values()->all(),
                'commercial_id' => $bundle->getKey(),
            ],
        );
    }
}
