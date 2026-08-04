<?php

namespace App\Services\Sales;

use App\Enums\Sales\DiscountType;
use App\Models\Sales\QuotationItem;
use Illuminate\Support\Collection;

class QuotationPricingService
{
    public function calculateItem(array $data): array
    {
        $quantity = (int) ($data['quantity'] ?? 1);
        $unitPrice = (float) ($data['unit_price'] ?? 0);
        $discountType = $data['discount_type'] ?? null;
        $discountValue = (float) ($data['discount_value'] ?? 0);
        $vatRate = (float) ($data['vat_rate'] ?? 0);

        $lineSubtotal = $quantity * $unitPrice;

        $discountAmount = match ($discountType) {
            DiscountType::Fixed->value => min($discountValue, $lineSubtotal),
            DiscountType::Percentage->value => $lineSubtotal * min($discountValue, 100) / 100,
            default => 0,
        };

        $taxableAmount = $lineSubtotal - $discountAmount;
        $vatAmount = $taxableAmount * $vatRate / 100;
        $lineTotal = $taxableAmount + $vatAmount;

        return [
            'line_subtotal' => round($lineSubtotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'vat_amount' => round($vatAmount, 2),
            'line_total' => round($lineTotal, 2),
        ];
    }

    public function calculateTotals(Collection $items): array
    {
        $subtotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;

        foreach ($items as $item) {
            $calculated = $this->calculateItem(
                $item instanceof QuotationItem ? $item->toArray() : $item
            );
            $subtotal += $calculated['line_subtotal'];
            $discountTotal += $calculated['discount_amount'];
            $taxTotal += $calculated['vat_amount'];
        }

        $grandTotal = $subtotal - $discountTotal + $taxTotal;

        return [
            'subtotal' => round($subtotal, 2),
            'discount_total' => round($discountTotal, 2),
            'tax_total' => round($taxTotal, 2),
            'grand_total' => round($grandTotal, 2),
        ];
    }

    public function validateDiscountLimit(float $discountAmount, float $limit): bool
    {
        return $discountAmount <= $limit;
    }
}
