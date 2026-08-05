<?php

namespace App\Services\Sales;

use App\Enums\Sales\QuotationStatus;
use App\Models\Sales\Quotation;
use App\Models\User;
use App\Services\Marketing\AuditLogService;
use Illuminate\Support\Facades\DB;

class QuotationRevisionService
{
    public function __construct(
        private readonly QuotationCodeGenerator $codeGenerator,
        private readonly QuotationPricingService $pricingService,
        private readonly QuotationStateMachine $stateMachine,
        private readonly AuditLogService $auditLog,
    ) {}

    public function createRevision(Quotation $quotation, User $user, array $updatedItems = [], array $updatedParams = []): Quotation
    {
        $this->validateRevision($quotation);

        return DB::transaction(function () use ($quotation, $user, $updatedItems, $updatedParams) {
            $items = $updatedItems ?: $quotation->items->toArray();
            $itemData = $this->buildItemData($items);
            $totals = $this->pricingService->calculateTotals(collect($itemData));

            $revision = Quotation::query()->create([
                'quotation_code' => $this->codeGenerator->generate(),
                'customer_id' => $quotation->customer_id,
                'assigned_staff_id' => $quotation->assigned_staff_id,
                'price_book_id' => $quotation->price_book_id,
                'bank_account_id' => $updatedParams['bank_account_id'] ?? $quotation->bank_account_id,
                'title' => $updatedParams['title'] ?? $quotation->title,
                'version' => $quotation->version + 1,
                'parent_quotation_id' => $quotation->parent_quotation_id ?? $quotation->id,
                'replaces_quotation_id' => $quotation->id,
                'quotation_date' => now()->toDateString(),
                'valid_until' => $updatedParams['valid_until'] ?? now()->addDays(30)->toDateString(),
                'currency' => $quotation->currency,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => $totals['tax_total'],
                'grand_total' => $totals['grand_total'],
                'status' => QuotationStatus::Draft,
                'payment_status' => 'unpaid',
                'email_status' => 'unsent',
                'customer_snapshot' => $quotation->customer_snapshot,
                'company_snapshot' => $quotation->company_snapshot,
                'payment_snapshot' => $updatedParams['payment_snapshot'] ?? $quotation->payment_snapshot,
                'terms_snapshot' => $updatedParams['terms_snapshot'] ?? $quotation->terms_snapshot,
                'public_token' => $this->codeGenerator->generatePublicToken(),
                'created_by' => $user->id,
            ]);

            $sortOrder = 0;
            foreach ($itemData as $item) {
                $calculated = $this->pricingService->calculateItem($item);
                $revision->items()->create([
                    'service_id' => $item['service_id'] ?? null,
                    'service_package_id' => $item['service_package_id'] ?? null,
                    'price_book_item_id' => $item['price_book_item_id'] ?? null,
                    'service_code_snapshot' => $item['service_code_snapshot'] ?? '',
                    'service_name_snapshot' => $item['service_name_snapshot'] ?? '',
                    'package_code_snapshot' => $item['package_code_snapshot'] ?? null,
                    'package_name_snapshot' => $item['package_name_snapshot'] ?? '',
                    'description_snapshot' => $item['description_snapshot'] ?? null,
                    'scope_snapshot' => $item['scope_snapshot'] ?? null,
                    'terms_snapshot' => $item['terms_snapshot'] ?? null,
                    'unit' => $item['unit'] ?? 'tháng',
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'discount_type' => $item['discount_type'] ?? null,
                    'discount_value' => $item['discount_value'] ?? 0,
                    'discount_amount' => $calculated['discount_amount'],
                    'vat_rate' => $item['vat_rate'] ?? 0,
                    'vat_amount' => $calculated['vat_amount'],
                    'line_subtotal' => $calculated['line_subtotal'],
                    'line_total' => $calculated['line_total'],
                    'sort_order' => $sortOrder++,
                ]);
            }

            $quotation->update([
                'status' => QuotationStatus::Superseded,
            ]);

            $this->auditLog->log('quotation.revision_created', $revision, [
                'previous_id' => $quotation->id,
                'previous_version' => $quotation->version,
            ], $revision->toArray());

            return $revision->fresh(['items', 'customer']);
        });
    }

    private function validateRevision(Quotation $quotation): void
    {
        if ($quotation->status === QuotationStatus::Draft) {
            return;
        }
        if (! $this->stateMachine->canTransition($quotation->status, QuotationStatus::Superseded)) {
            throw new \InvalidArgumentException('This quotation cannot be revised.');
        }
    }

    private function buildItemData(array $items): array
    {
        return array_map(fn ($item) => [
            'service_id' => $item['service_id'] ?? null,
            'service_package_id' => $item['service_package_id'] ?? null,
            'price_book_item_id' => $item['price_book_item_id'] ?? null,
            'service_code_snapshot' => $item['service_code_snapshot'] ?? $item['service_code_snapshot'] ?? '',
            'service_name_snapshot' => $item['service_name_snapshot'] ?? $item['service_name_snapshot'] ?? '',
            'package_code_snapshot' => $item['package_code_snapshot'] ?? null,
            'package_name_snapshot' => $item['package_name_snapshot'] ?? '',
            'description_snapshot' => $item['description_snapshot'] ?? null,
            'scope_snapshot' => $item['scope_snapshot'] ?? null,
            'terms_snapshot' => $item['terms_snapshot'] ?? null,
            'unit' => $item['unit'] ?? 'tháng',
            'quantity' => $item['quantity'] ?? 1,
            'unit_price' => $item['unit_price'] ?? 0,
            'discount_type' => $item['discount_type'] ?? null,
            'discount_value' => $item['discount_value'] ?? 0,
            'vat_rate' => $item['vat_rate'] ?? 0,
        ], $items);
    }
}
