<?php

namespace Dth\Commercial\Services;

use DateTimeInterface;
use Dth\Commercial\Enums\AudienceType;
use Dth\Commercial\Enums\BillingPeriodUnit;
use Dth\Commercial\Enums\BundlePricingType;
use Dth\Commercial\Enums\OpportunityItemType;
use Dth\Commercial\Enums\OpportunityStage;
use Dth\Commercial\Enums\ServiceStatus;
use Dth\Commercial\Models\Bundle;
use Dth\Commercial\Models\BundleItem;
use Dth\Commercial\Models\Opportunity;
use Dth\Commercial\Models\OpportunityItem;
use Dth\Commercial\Models\Product;
use Dth\Commercial\Models\ProductPrice;
use Dth\Commercial\Models\Service;
use Dth\Commercial\Support\CrmLeadLookup;
use Dth\Commercial\Support\UiText;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;
use Throwable;

final class CommercialDataExchangeService
{
    /** @return array<int,string> */
    public function headers(string $entity): array
    {
        return match ($entity) {
            'services' => [
                'service_code', 'name', 'slug', 'status', 'description',
                'default_scope', 'default_terms', 'sort_order',
            ],
            'products' => [
                'service_code', 'product_code', 'name', 'status', 'description',
                'audience_type', 'unit', 'default_quantity', 'sort_order',
                'price_code', 'currency', 'billing_period', 'billing_period_unit',
                'price', 'renewal_price', 'setup_fee', 'is_default_price', 'price_status',
                'valid_from', 'valid_until',
            ],
            'bundles' => [
                'bundle_code', 'name', 'status', 'description', 'primary_service_code',
                'audience_type', 'pricing_type', 'currency', 'billing_period',
                'billing_period_unit', 'fixed_price', 'renewal_price', 'setup_fee', 'sort_order',
                'product_code', 'product_price_code', 'product_quantity', 'required', 'price_override', 'item_sort_order',
            ],
            'opportunities' => [
                'opportunity_code', 'title', 'lead_reference', 'company', 'contact',
                'stage', 'currency', 'probability', 'expected_close_date', 'owner', 'lost_reason',
                'line_key', 'item_type', 'item_code', 'price_code', 'item_description', 'quantity',
                'unit_price', 'discount_percent', 'setup_fee',
            ],
            default => throw new RuntimeException("Unsupported Commercial import entity: {$entity}"),
        };
    }

    /** @return array{imported:int,updated:int,errors:array<int,array{row:int,message:string}>} */
    public function import(string $entity, string $path, ?string $extension = null): array
    {
        $rows = $this->readRows($path, $extension);
        if ($rows === []) {
            throw new RuntimeException(UiText::get('data.empty_file', 'The import file does not contain any data rows.'));
        }

        $imported = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                $result = DB::transaction(fn (): string => $this->importRow($entity, $row));
                $result === 'updated' ? $updated++ : $imported++;
            } catch (Throwable $exception) {
                $errors[] = [
                    'row' => $index + 2,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return compact('imported', 'updated', 'errors');
    }

    /** @param array<string,mixed> $row */
    private function importRow(string $entity, array $row): string
    {
        return match ($entity) {
            'services' => $this->importService($row),
            'products' => $this->importProduct($row),
            'bundles' => $this->importBundle($row),
            'opportunities' => $this->importOpportunity($row),
            default => throw new RuntimeException("Unsupported Commercial import entity: {$entity}"),
        };
    }

    /** @param array<string,mixed> $row */
    private function importService(array $row): string
    {
        $code = strtoupper($this->requiredString($row, 'service_code'));
        $name = $this->requiredString($row, 'name');
        $service = Service::withTrashed()->where('service_code', $code)->first();
        $isUpdate = $service !== null;

        $data = ['service_code' => $code, 'name' => $name, 'updated_by' => auth()->id()];
        if ($this->hasValue($row, 'slug')) {
            $data['slug'] = $this->stringOrNull($row['slug']);
        } elseif (! $isUpdate) {
            $data['slug'] = null;
        }
        $this->assignString($data, $row, 'description', $isUpdate);
        $this->assignString($data, $row, 'default_scope', $isUpdate);
        $this->assignString($data, $row, 'default_terms', $isUpdate);
        $data['status'] = $this->hasValue($row, 'status')
            ? $this->enumValue(ServiceStatus::class, $row['status'], 'status')
            : ($isUpdate ? $service->status : ServiceStatus::Active->value);
        $data['sort_order'] = $this->hasValue($row, 'sort_order')
            ? $this->nonNegativeInteger($row['sort_order'], 'sort_order')
            : ($isUpdate ? $service->sort_order : 0);

        if ($service) {
            $service->trashed() && $service->restore();
            $service->update($data);
            return 'updated';
        }

        $data['created_by'] = auth()->id();
        Service::create($data);
        return 'imported';
    }

    /** @param array<string,mixed> $row */
    private function importProduct(array $row): string
    {
        $serviceCode = $this->requiredString($row, 'service_code');
        $service = $this->findService($serviceCode);
        if (! $service) {
            throw new RuntimeException(strtr(UiText::get('data.service_not_found', 'Service :code was not found or is deleted.'), [':code' => $serviceCode]));
        }

        $code = strtoupper($this->requiredString($row, 'product_code'));
        $name = $this->requiredString($row, 'name');
        $product = Product::withTrashed()->where('product_code', $code)->first();
        $isUpdate = $product !== null;

        $data = [
            'service_id' => $service->getKey(),
            'product_code' => $code,
            'name' => $name,
            'updated_by' => auth()->id(),
        ];
        $this->assignString($data, $row, 'description', $isUpdate);
        $data['status'] = $this->hasValue($row, 'status')
            ? $this->enumValue(ServiceStatus::class, $row['status'], 'status')
            : ($isUpdate ? $product->status : ServiceStatus::Active->value);
        $data['audience_type'] = $this->hasValue($row, 'audience_type')
            ? $this->enumValue(AudienceType::class, $row['audience_type'], 'audience_type')
            : ($isUpdate ? $product->audience_type : AudienceType::Both->value);
        $data['unit'] = $this->hasValue($row, 'unit')
            ? ($this->stringOrNull($row['unit']) ?: 'item')
            : ($isUpdate ? $product->unit : 'item');
        $data['default_quantity'] = $this->hasValue($row, 'default_quantity')
            ? $this->positiveNumber($row['default_quantity'], 'default_quantity')
            : ($isUpdate ? $product->default_quantity : 1);
        $data['sort_order'] = $this->hasValue($row, 'sort_order')
            ? $this->nonNegativeInteger($row['sort_order'], 'sort_order')
            : ($isUpdate ? $product->sort_order : 0);

        if ($product) {
            $product->trashed() && $product->restore();
            $product->update($data);
        } else {
            $data['created_by'] = auth()->id();
            $product = Product::create($data);
        }

        if ($this->rowHasAny($row, ['price_code', 'currency', 'billing_period', 'billing_period_unit', 'price', 'renewal_price', 'setup_fee', 'is_default_price', 'price_status', 'valid_from', 'valid_until'])) {
            $this->importProductPrice($product, $row);
        }

        return $isUpdate ? 'updated' : 'imported';
    }

    /** @param array<string,mixed> $row */
    private function importProductPrice(Product $product, array $row): void
    {
        $currency = $this->hasValue($row, 'currency') ? $this->currencyCode($row['currency']) : 'VND';
        $period = $this->hasValue($row, 'billing_period') ? $this->positiveIntegerOrNull($row['billing_period'], 'billing_period') : null;
        $periodUnit = $this->hasValue($row, 'billing_period_unit')
            ? $this->nullableEnumValue(BillingPeriodUnit::class, $row['billing_period_unit'], 'billing_period_unit')
            : null;
        $priceCode = strtoupper($this->stringOrNull($row['price_code'] ?? null) ?: $this->generatedPriceCode($currency, $period, $periodUnit));

        $price = ProductPrice::query()
            ->where('product_id', $product->getKey())
            ->where('price_code', $priceCode)
            ->first();
        $isNew = $price === null;

        $data = [
            'price_code' => $priceCode,
            'currency' => $currency,
            'billing_period' => $period,
            'billing_period_unit' => $periodUnit,
            'price' => $this->hasValue($row, 'price') ? $this->nonNegativeNumberOrNull($row['price'], 'price') : ($price?->price),
            'renewal_price' => $this->hasValue($row, 'renewal_price') ? $this->nonNegativeNumberOrNull($row['renewal_price'], 'renewal_price') : ($price?->renewal_price),
            'setup_fee' => $this->hasValue($row, 'setup_fee') ? ($this->nonNegativeNumberOrNull($row['setup_fee'], 'setup_fee') ?? 0) : ($price?->setup_fee ?? 0),
            'status' => $this->hasValue($row, 'price_status')
                ? $this->enumValue(ServiceStatus::class, $row['price_status'], 'price_status')
                : ($price?->status ?? ServiceStatus::Active->value),
            'valid_from' => $this->hasValue($row, 'valid_from') ? $this->dateOrNull($row['valid_from'], 'valid_from') : $price?->valid_from,
            'valid_until' => $this->hasValue($row, 'valid_until') ? $this->dateOrNull($row['valid_until'], 'valid_until') : $price?->valid_until,
        ];

        if ($this->hasValue($row, 'is_default_price')) {
            $data['is_default'] = $this->booleanValue($row['is_default_price'], 'is_default_price');
        } elseif ($isNew) {
            $data['is_default'] = ! $product->prices()->where('currency', $currency)->exists();
        }

        if ($price) {
            $price->update($data);
        } else {
            $product->prices()->create($data);
        }
    }

    /** @param array<string,mixed> $row */
    private function importBundle(array $row): string
    {
        $code = strtoupper($this->requiredString($row, 'bundle_code'));
        $name = $this->requiredString($row, 'name');
        $bundle = Bundle::withTrashed()->where('bundle_code', $code)->first();
        $isUpdate = $bundle !== null;

        $primaryService = null;
        if ($this->hasValue($row, 'primary_service_code')) {
            $primaryService = $this->findService((string) $row['primary_service_code']);
            if (! $primaryService) {
                throw new RuntimeException(strtr(UiText::get('data.service_not_found', 'Service :code was not found or is deleted.'), [':code' => (string) $row['primary_service_code']]));
            }
        }

        $data = [
            'bundle_code' => $code,
            'name' => $name,
            'updated_by' => auth()->id(),
        ];
        if ($this->hasValue($row, 'primary_service_code')) {
            $data['primary_service_id'] = $primaryService?->getKey();
        } elseif (! $isUpdate) {
            $data['primary_service_id'] = null;
        }
        $this->assignString($data, $row, 'description', $isUpdate);
        $data['status'] = $this->hasValue($row, 'status')
            ? $this->enumValue(ServiceStatus::class, $row['status'], 'status')
            : ($isUpdate ? $bundle->status : ServiceStatus::Active->value);
        $data['audience_type'] = $this->hasValue($row, 'audience_type')
            ? $this->enumValue(AudienceType::class, $row['audience_type'], 'audience_type')
            : ($isUpdate ? $bundle->audience_type : AudienceType::Both->value);
        $data['pricing_type'] = $this->hasValue($row, 'pricing_type')
            ? $this->enumValue(BundlePricingType::class, $row['pricing_type'], 'pricing_type')
            : ($isUpdate ? $bundle->pricing_type : BundlePricingType::ComponentSum->value);
        $data['currency'] = $this->hasValue($row, 'currency')
            ? $this->currencyCode($row['currency'])
            : ($isUpdate ? $bundle->currency : 'VND');
        $data['billing_period'] = $this->hasValue($row, 'billing_period')
            ? $this->positiveIntegerOrNull($row['billing_period'], 'billing_period')
            : ($isUpdate ? $bundle->billing_period : null);
        $data['billing_period_unit'] = $this->hasValue($row, 'billing_period_unit')
            ? $this->nullableEnumValue(BillingPeriodUnit::class, $row['billing_period_unit'], 'billing_period_unit')
            : ($isUpdate ? $bundle->billing_period_unit : null);
        $data['fixed_price'] = $this->hasValue($row, 'fixed_price')
            ? $this->nonNegativeNumberOrNull($row['fixed_price'], 'fixed_price')
            : ($isUpdate ? $bundle->fixed_price : null);
        $data['renewal_price'] = $this->hasValue($row, 'renewal_price')
            ? $this->nonNegativeNumberOrNull($row['renewal_price'], 'renewal_price')
            : ($isUpdate ? $bundle->renewal_price : null);
        $data['setup_fee'] = $this->hasValue($row, 'setup_fee')
            ? ($this->nonNegativeNumberOrNull($row['setup_fee'], 'setup_fee') ?? 0)
            : ($isUpdate ? $bundle->setup_fee : 0);
        $data['sort_order'] = $this->hasValue($row, 'sort_order')
            ? $this->nonNegativeInteger($row['sort_order'], 'sort_order')
            : ($isUpdate ? $bundle->sort_order : 0);

        if ($bundle) {
            $bundle->trashed() && $bundle->restore();
            $bundle->update($data);
        } else {
            $data['created_by'] = auth()->id();
            $bundle = Bundle::create($data);
        }

        if ($this->hasValue($row, 'product_code')) {
            $productCode = strtoupper((string) $row['product_code']);
            $product = Product::query()->where('product_code', $productCode)->first();
            if (! $product) {
                throw new RuntimeException(strtr(UiText::get('data.product_not_found', 'Product :code was not found or is deleted.'), [':code' => $productCode]));
            }

            $item = BundleItem::query()
                ->where('bundle_id', $bundle->getKey())
                ->where('product_id', $product->getKey())
                ->first();

            $productPrice = null;
            if ($this->hasValue($row, 'product_price_code')) {
                $priceCode = strtoupper($this->requiredString($row, 'product_price_code'));
                $productPrice = ProductPrice::query()
                    ->where('product_id', $product->getKey())
                    ->where('price_code', $priceCode)
                    ->first();
                if (! $productPrice) {
                    throw new RuntimeException(strtr(UiText::get('data.product_price_not_found', 'Price :code was not found for product :product.'), [':code' => $priceCode, ':product' => $productCode]));
                }
                if (strtoupper((string) $productPrice->currency) !== strtoupper((string) $bundle->currency)) {
                    throw new RuntimeException(strtr(UiText::get('data.product_price_currency_mismatch', 'Price :code uses a different currency from this record.'), [':code' => $priceCode]));
                }
            } elseif (! $item) {
                $productPrice = $product->preferredPrice((string) $bundle->currency);
            }

            $itemData = [
                'product_id' => $product->getKey(),
                'product_price_id' => $productPrice?->getKey() ?? $item?->product_price_id,
                'quantity' => $this->hasValue($row, 'product_quantity') ? $this->positiveNumber($row['product_quantity'], 'product_quantity') : ($item?->quantity ?? 1),
                'required' => $this->hasValue($row, 'required') ? $this->booleanValue($row['required'], 'required') : ($item?->required ?? true),
                'price_override' => $this->hasValue($row, 'price_override') ? $this->nonNegativeNumberOrNull($row['price_override'], 'price_override') : $item?->price_override,
                'sort_order' => $this->hasValue($row, 'item_sort_order') ? $this->nonNegativeInteger($row['item_sort_order'], 'item_sort_order') : ($item?->sort_order ?? 0),
            ];
            if ($item) {
                $item->update($itemData);
            } else {
                $bundle->items()->create($itemData);
            }
        }

        return $isUpdate ? 'updated' : 'imported';
    }

    /** @param array<string,mixed> $row */
    private function importOpportunity(array $row): string
    {
        $title = $this->requiredString($row, 'title');
        $providedCode = $this->stringOrNull($row['opportunity_code'] ?? null);
        $opportunity = $providedCode ? Opportunity::withTrashed()->where('opportunity_code', $providedCode)->first() : null;
        $isUpdate = $opportunity !== null;

        $data = ['title' => $title, 'updated_by' => auth()->id()];

        if ($this->hasValue($row, 'lead_reference')) {
            $leadReference = $this->stringOrNull($row['lead_reference']);
            $data['lead_reference'] = $leadReference;
            $snapshot = app(CrmLeadLookup::class)->snapshot($leadReference);
            if ($snapshot) {
                $data['lead_code_snapshot'] = $snapshot['lead_code'];
                $data['contact_reference'] = $snapshot['contact_reference'];
                $data['contact_name_snapshot'] = $snapshot['contact_name'];
                $data['company_reference'] = $snapshot['company_reference'];
                $data['company_name_snapshot'] = $snapshot['company_name'];
                $data['assigned_employee_reference'] = $snapshot['owner_reference'];
                $data['assigned_employee_name_snapshot'] = $snapshot['owner_name'];

                $service = filled($snapshot['service_reference']) ? $this->findService((string) $snapshot['service_reference']) : null;
                $data['service_id'] = $service?->getKey();
                $data['service_reference'] = $service?->reference() ?? $snapshot['service_reference'];
                $data['service_name_snapshot'] = $service?->name ?? ($snapshot['service_name'] ?: $snapshot['service_reference']);
            }
        } elseif (! $isUpdate) {
            $data['lead_reference'] = null;
        }

        foreach (['company' => 'company_name_snapshot', 'contact' => 'contact_name_snapshot', 'owner' => 'assigned_employee_name_snapshot'] as $column => $target) {
            if ($this->hasValue($row, $column)) {
                $data[$target] = $this->stringOrNull($row[$column]);
            } elseif (! $isUpdate && ! array_key_exists($target, $data)) {
                $data[$target] = null;
            }
        }

        // Accept legacy service_code files as CRM/service-interest context only.
        if ($this->hasValue($row, 'service_code')) {
            $service = $this->findService((string) $row['service_code']);
            if (! $service) {
                throw new RuntimeException(strtr(UiText::get('data.service_not_found', 'Service :code was not found or is deleted.'), [':code' => (string) $row['service_code']]));
            }
            $data['service_id'] = $service->getKey();
            $data['service_reference'] = $service->reference();
            $data['service_name_snapshot'] = $service->name;
        }

        $stage = $isUpdate
            ? ($opportunity->stage instanceof OpportunityStage ? $opportunity->stage : OpportunityStage::from((string) $opportunity->stage))
            : OpportunityStage::Discovery;
        if ($this->hasValue($row, 'stage')) {
            $requestedStage = OpportunityStage::from($this->enumValue(OpportunityStage::class, $row['stage'], 'stage'));
            if ($isUpdate && $requestedStage !== $stage) {
                throw new RuntimeException(UiText::get('data.stage_update_blocked', 'Existing opportunity stages cannot be changed by import. Use the Move stage workflow instead.'));
            }
            $stage = $requestedStage;
            $data['stage'] = $stage->value;
        } elseif (! $isUpdate) {
            $data['stage'] = $stage->value;
        }

        $data['currency'] = $this->hasValue($row, 'currency')
            ? $this->currencyCode($row['currency'])
            : ($isUpdate ? $opportunity->currency : 'VND');
        if ($this->hasValue($row, 'probability')) {
            $data['probability'] = $this->percentage($row['probability'], 'probability');
        } elseif (! $isUpdate) {
            $data['probability'] = $stage->probability();
        }
        if ($this->hasValue($row, 'expected_close_date')) {
            $data['expected_close_date'] = $this->dateOrNull($row['expected_close_date'], 'expected_close_date');
        } elseif (! $isUpdate) {
            $data['expected_close_date'] = null;
        }
        if ($this->hasValue($row, 'lost_reason')) {
            $data['lost_reason'] = $this->stringOrNull($row['lost_reason']);
        } elseif (! $isUpdate) {
            $data['lost_reason'] = null;
        }

        if ($stage === OpportunityStage::Lost && blank($data['lost_reason'] ?? $opportunity?->lost_reason)) {
            throw new RuntimeException(UiText::get('validation.lost_reason_required', 'Lost reason is required.'));
        }
        if ($this->hasValue($row, 'stage')) {
            $data['won_at'] = $stage === OpportunityStage::Won ? ($opportunity?->won_at ?: now()) : null;
            $data['lost_at'] = $stage === OpportunityStage::Lost ? ($opportunity?->lost_at ?: now()) : null;
            if ($stage !== OpportunityStage::Lost) {
                $data['lost_reason'] = null;
            }
        }

        if ($opportunity) {
            $opportunity->trashed() && $opportunity->restore();
            $opportunity->update($data);
        } else {
            $data['opportunity_code'] = $providedCode ?: app(OpportunityCodeGenerator::class)->next();
            $data['created_by'] = auth()->id();
            $opportunity = Opportunity::create($data);
        }

        if ($this->hasValue($row, 'item_code')) {
            $this->importOpportunityItem($opportunity, $row);
        }

        return $isUpdate ? 'updated' : 'imported';
    }

    /** @param array<string,mixed> $row */
    private function importOpportunityItem(Opportunity $opportunity, array $row): void
    {
        $itemCode = strtoupper($this->requiredString($row, 'item_code'));
        $typeValue = $this->stringOrNull($row['item_type'] ?? null);
        if ($typeValue === null) {
            $typeValue = Product::query()->where('product_code', $itemCode)->exists()
                ? OpportunityItemType::Product->value
                : OpportunityItemType::Bundle->value;
        }
        $type = OpportunityItemType::from($this->enumValue(OpportunityItemType::class, $typeValue, 'item_type'));

        $product = null;
        $productPrice = null;
        $bundle = null;
        if ($type === OpportunityItemType::Product) {
            $product = Product::query()->where('product_code', $itemCode)->first();
            if (! $product) {
                throw new RuntimeException(strtr(UiText::get('data.product_not_found', 'Product :code was not found or is deleted.'), [':code' => $itemCode]));
            }
            if ($this->hasValue($row, 'price_code')) {
                $priceCode = strtoupper($this->requiredString($row, 'price_code'));
                $productPrice = ProductPrice::query()
                    ->where('product_id', $product->getKey())
                    ->where('price_code', $priceCode)
                    ->first();
                if (! $productPrice) {
                    throw new RuntimeException(strtr(UiText::get('data.product_price_not_found', 'Price :code was not found for product :product.'), [':code' => $priceCode, ':product' => $itemCode]));
                }
                if (strtoupper((string) $productPrice->currency) !== strtoupper((string) ($opportunity->currency ?: 'VND'))) {
                    throw new RuntimeException(strtr(UiText::get('data.product_price_currency_mismatch', 'Price :code uses a different currency from this record.'), [':code' => $priceCode]));
                }
            }
        } else {
            $bundle = Bundle::query()->where('bundle_code', $itemCode)->first();
            if (! $bundle) {
                throw new RuntimeException(strtr(UiText::get('data.bundle_not_found', 'Bundle :code was not found or is deleted.'), [':code' => $itemCode]));
            }
            if (strtoupper((string) $bundle->currency) !== strtoupper((string) ($opportunity->currency ?: 'VND'))) {
                throw new RuntimeException(strtr(UiText::get('data.bundle_currency_mismatch', 'Bundle :code uses a different currency from this opportunity.'), [':code' => $itemCode]));
            }
        }

        $lineKey = $this->stringOrNull($row['line_key'] ?? null) ?: strtoupper($type->value).':'.$itemCode;
        $item = OpportunityItem::query()
            ->where('opportunity_id', $opportunity->getKey())
            ->where('line_key', $lineKey)
            ->first();

        if ($type === OpportunityItemType::Product && ! $productPrice && ! $item?->product_price_id) {
            $productPrice = $product?->preferredPrice((string) ($opportunity->currency ?: 'VND'));
        }

        $data = [
            'line_key' => $lineKey,
            'item_type' => $type->value,
            'product_id' => $product?->getKey(),
            'product_price_id' => $type === OpportunityItemType::Product
                ? ($productPrice?->getKey() ?? $item?->product_price_id)
                : null,
            'bundle_id' => $bundle?->getKey(),
            'description_snapshot' => $this->hasValue($row, 'item_description') ? $this->stringOrNull($row['item_description']) : $item?->description_snapshot,
            'quantity' => $this->hasValue($row, 'quantity') ? $this->positiveNumber($row['quantity'], 'quantity') : ($item?->quantity ?? 1),
            'unit_price' => $this->hasValue($row, 'unit_price')
                ? $this->nonNegativeNumberOrNull($row['unit_price'], 'unit_price')
                : ($productPrice?->price ?? $item?->unit_price),
            'discount_percent' => $this->hasValue($row, 'discount_percent') ? $this->percentageNumber($row['discount_percent'], 'discount_percent') : ($item?->discount_percent ?? 0),
            'setup_fee' => $this->hasValue($row, 'setup_fee')
                ? ($this->nonNegativeNumberOrNull($row['setup_fee'], 'setup_fee') ?? 0)
                : ($productPrice?->setup_fee ?? $item?->setup_fee ?? 0),
            'billing_period' => $productPrice?->billing_period ?? $item?->billing_period,
            'billing_period_unit' => $productPrice?->billing_period_unit?->value ?? ($item?->billing_period_unit instanceof \BackedEnum ? $item->billing_period_unit->value : $item?->billing_period_unit),
            'currency' => $opportunity->currency ?: 'VND',
        ];

        if ($item) {
            $item->update($data);
        } else {
            $opportunity->items()->create($data);
        }
    }

    private function findService(string $reference): ?Service
    {
        $reference = trim($reference);
        return Service::query()
            ->where('service_code', strtoupper($reference))
            ->orWhere('slug', $reference)
            ->first();
    }

    private function generatedPriceCode(string $currency, ?int $period, ?string $periodUnit): string
    {
        $period ??= 1;
        $periodUnit = $periodUnit ?: 'custom';
        return strtoupper("LIST-{$currency}-{$period}-{$periodUnit}");
    }

    /** @param array<string,mixed> $row @param array<int,string> $fields */
    private function rowHasAny(array $row, array $fields): bool
    {
        foreach ($fields as $field) {
            if ($this->hasValue($row, $field)) {
                return true;
            }
        }
        return false;
    }

    /** @return array<int,array<string,mixed>> */
    private function readRows(string $path, ?string $extension = null): array
    {
        $extension = strtolower($extension ?: pathinfo($path, PATHINFO_EXTENSION));
        return $extension === 'xlsx' ? $this->readXlsx($path) : $this->readCsv($path);
    }

    /** @return array<int,array<string,mixed>> */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException(UiText::get('data.unreadable_file', 'Unable to read the uploaded file.'));
        }
        $headers = fgetcsv($handle);
        if (! is_array($headers)) {
            fclose($handle);
            throw new RuntimeException(UiText::get('data.missing_headers', 'The file is missing a header row.'));
        }
        $headers = $this->normalizeHeaders($headers);
        $rows = [];
        while (($values = fgetcsv($handle)) !== false) {
            if ($this->rowIsEmpty($values)) {
                continue;
            }
            $rows[] = $this->combineRow($headers, $values);
        }
        fclose($handle);
        return $rows;
    }

    /** @return array<int,array<string,mixed>> */
    private function readXlsx(string $path): array
    {
        $reader = new XlsxReader();
        $reader->open($path);
        $headers = null;
        $rows = [];
        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $values = $row->toArray();
                    if ($headers === null) {
                        $headers = $this->normalizeHeaders($values);
                        continue;
                    }
                    if ($this->rowIsEmpty($values)) {
                        continue;
                    }
                    $rows[] = $this->combineRow($headers, $values);
                }
                break;
            }
        } finally {
            $reader->close();
        }
        if ($headers === null) {
            throw new RuntimeException(UiText::get('data.missing_headers', 'The file is missing a header row.'));
        }
        return $rows;
    }

    /** @param array<int,mixed> $headers @return array<int,string> */
    private function normalizeHeaders(array $headers): array
    {
        return array_map(function (mixed $header): string {
            $value = trim((string) $header);
            $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
            $value = strtolower($value);
            $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? $value;
            return trim($value, '_');
        }, $headers);
    }

    /** @param array<int,string> $headers @param array<int,mixed> $values @return array<string,mixed> */
    private function combineRow(array $headers, array $values): array
    {
        $values = array_pad($values, count($headers), null);
        $values = array_slice($values, 0, count($headers));
        return array_combine($headers, $values) ?: [];
    }

    /** @param array<int,mixed> $values */
    private function rowIsEmpty(array $values): bool
    {
        foreach ($values as $value) {
            if ($value instanceof DateTimeInterface || ($value !== null && trim((string) $value) !== '')) {
                return false;
            }
        }
        return true;
    }

    /** @param array<string,mixed> $row */
    private function requiredString(array $row, string $field): string
    {
        $value = $this->stringOrNull($row[$field] ?? null);
        if ($value === null) {
            throw new RuntimeException(strtr(UiText::get('data.field_required', ':field is required.'), [':field' => $field]));
        }
        return $value;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    /** @param array<string,mixed> $row */
    private function hasValue(array $row, string $field): bool
    {
        if (! array_key_exists($field, $row)) {
            return false;
        }
        return $row[$field] instanceof DateTimeInterface || $this->stringOrNull($row[$field]) !== null;
    }

    /** @param array<string,mixed> $data @param array<string,mixed> $row */
    private function assignString(array &$data, array $row, string $field, bool $isUpdate): void
    {
        if (array_key_exists($field, $row)) {
            $data[$field] = $this->stringOrNull($row[$field]);
        } elseif (! $isUpdate) {
            $data[$field] = null;
        }
    }

    /** @param class-string<\BackedEnum> $enum */
    private function enumValue(string $enum, mixed $value, string $field): string
    {
        $normalized = strtolower(trim((string) $value));
        foreach ($enum::cases() as $case) {
            if ($case->value === $normalized) {
                return $case->value;
            }
        }
        throw new RuntimeException(strtr(UiText::get('data.invalid_enum', 'Invalid value for :field: :value.'), [':field' => $field, ':value' => (string) $value]));
    }

    /** @param class-string<\BackedEnum> $enum */
    private function nullableEnumValue(string $enum, mixed $value, string $field): ?string
    {
        $value = $this->stringOrNull($value);
        return $value === null ? null : $this->enumValue($enum, $value, $field);
    }

    private function nonNegativeInteger(mixed $value, string $field): int
    {
        if (! is_numeric($value) || (int) $value < 0) {
            throw new RuntimeException("{$field} must be a non-negative integer.");
        }
        return (int) $value;
    }

    private function positiveIntegerOrNull(mixed $value, string $field): ?int
    {
        $value = $this->stringOrNull($value);
        if ($value === null) {
            return null;
        }
        if (! is_numeric($value) || (int) $value < 1) {
            throw new RuntimeException("{$field} must be at least 1.");
        }
        return (int) $value;
    }

    private function positiveNumber(mixed $value, string $field): float
    {
        if (! is_numeric($value) || (float) $value <= 0) {
            throw new RuntimeException("{$field} must be greater than 0.");
        }
        return (float) $value;
    }

    private function percentage(mixed $value, string $field): int
    {
        if (! is_numeric($value) || (int) $value < 0 || (int) $value > 100) {
            throw new RuntimeException("{$field} must be between 0 and 100.");
        }
        return (int) $value;
    }

    private function percentageNumber(mixed $value, string $field): float
    {
        if (! is_numeric($value) || (float) $value < 0 || (float) $value > 100) {
            throw new RuntimeException("{$field} must be between 0 and 100.");
        }
        return (float) $value;
    }

    private function nonNegativeNumberOrNull(mixed $value, string $field): ?float
    {
        $value = $this->stringOrNull($value);
        if ($value === null) {
            return null;
        }
        $normalized = str_replace([',', ' '], '', $value);
        if (! is_numeric($normalized) || (float) $normalized < 0) {
            throw new RuntimeException("{$field} must be a non-negative number.");
        }
        return (float) $normalized;
    }

    private function booleanValue(mixed $value, string $field): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        $normalized = strtolower(trim((string) $value));
        if (in_array($normalized, ['1', 'true', 'yes', 'y', 'on'], true)) {
            return true;
        }
        if (in_array($normalized, ['0', 'false', 'no', 'n', 'off'], true)) {
            return false;
        }
        throw new RuntimeException("{$field} must be true/false, yes/no, or 1/0.");
    }

    private function currencyCode(mixed $value): string
    {
        $currency = strtoupper(trim((string) $value));
        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new RuntimeException('currency must be a 3-letter ISO code such as VND or USD.');
        }
        return $currency;
    }

    private function dateOrNull(mixed $value, string $field): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        $value = $this->stringOrNull($value);
        if ($value === null) {
            return null;
        }
        foreach (['Y-m-d', 'd/m/Y', 'm/d/Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!'.$format, $value);
            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }
        throw new RuntimeException("{$field} must use YYYY-MM-DD or DD/MM/YYYY.");
    }
}
