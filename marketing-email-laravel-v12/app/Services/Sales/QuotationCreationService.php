<?php

namespace App\Services\Sales;

use App\Enums\Crm\CustomerStatus;
use App\Enums\Sales\DiscountType;
use App\Enums\Sales\QuotationStatus;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerAssignment;
use App\Models\Sales\BankAccount;
use App\Models\Sales\PriceBook;
use App\Models\Sales\PriceBookItem;
use App\Models\Sales\Quotation;
use App\Models\User;
use App\Services\Marketing\AuditLogService;
use Illuminate\Support\Facades\DB;

class QuotationCreationService
{
    public function __construct(
        private readonly QuotationCodeGenerator $codeGenerator,
        private readonly QuotationPricingService $pricingService,
        private readonly PriceBookAccessService $priceBookAccess,
        private readonly QuotationStateMachine $stateMachine,
        private readonly AuditLogService $auditLog,
    ) {}

    public function create(
        Customer $customer,
        User $user,
        PriceBook $priceBook,
        array $items,
        array $params = [],
    ): Quotation {
        $staff = $user->staff;

        $this->validateCustomer($customer);
        $this->validateAssignment($customer, $user);
        $this->validatePriceBookAccess($user, $priceBook, $customer);

        $itemData = $this->buildItemData($items, $priceBook);
        $totals = $this->pricingService->calculateTotals(collect($itemData));

        return DB::transaction(function () use ($customer, $user, $staff, $priceBook, $itemData, $totals, $params) {
            $code = $this->codeGenerator->generate();

            $quotation = Quotation::query()->create([
                'quotation_code' => $code,
                'customer_id' => $customer->id,
                'assigned_staff_id' => $staff?->id,
                'price_book_id' => $priceBook->id,
                'bank_account_id' => $params['bank_account_id'] ?? null,
                'title' => $params['title'] ?? 'Báo giá '.$customer->display_name,
                'version' => 1,
                'quotation_date' => $params['quotation_date'] ?? now()->toDateString(),
                'valid_until' => $params['valid_until'] ?? now()->addDays(30)->toDateString(),
                'currency' => $priceBook->currency,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => $totals['tax_total'],
                'grand_total' => $totals['grand_total'],
                'status' => QuotationStatus::Draft,
                'payment_status' => 'unpaid',
                'email_status' => 'unsent',
                'customer_snapshot' => $this->buildCustomerSnapshot($customer),
                'company_snapshot' => $customer->customer_type === 'business' ? $this->buildCompanySnapshot($customer) : null,
                'payment_snapshot' => $this->buildPaymentSnapshot($params),
                'terms_snapshot' => $this->buildTermsSnapshot($priceBook, $params),
                'public_token' => $this->codeGenerator->generatePublicToken(),
                'created_by' => $user->id,
            ]);

            $this->saveItems($quotation, $itemData);

            $this->auditLog->log('quotation.created', $quotation, [], $quotation->toArray());

            return $quotation->fresh(['items', 'customer']);
        });
    }

    public function update(Quotation $quotation, array $items, array $params = []): Quotation
    {
        $itemData = $this->buildItemData($items, $quotation->priceBook);
        $totals = $this->pricingService->calculateTotals(collect($itemData));

        return DB::transaction(function () use ($quotation, $itemData, $totals, $params) {
            $quotation->items()->delete();
            $this->saveItems($quotation, $itemData);

            $quotation->update([
                'bank_account_id' => $params['bank_account_id'] ?? $quotation->bank_account_id,
                'title' => $params['title'] ?? $quotation->title,
                'valid_until' => $params['valid_until'] ?? $quotation->valid_until,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => $totals['tax_total'],
                'grand_total' => $totals['grand_total'],
            ]);

            $this->auditLog->log('quotation.updated', $quotation, [], $quotation->toArray());

            return $quotation->fresh(['items', 'customer']);
        });
    }

    private function saveItems(Quotation $quotation, array $itemData): void
    {
        $sortOrder = 0;
        foreach ($itemData as $item) {
            $calculated = $this->pricingService->calculateItem($item);
            $quotation->items()->create([
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
    }

    private function validateCustomer(Customer $customer): void
    {
        if ($customer->status === CustomerStatus::Archived || $customer->status === CustomerStatus::Blocked) {
            throw new \InvalidArgumentException('Customer is not available for quotations.');
        }
    }

    private function validateAssignment(Customer $customer, User $user): void
    {
        if ($user->isAdmin() || $user->isCustomerServiceManager()) {
            return;
        }

        $staff = $user->staff;
        if (! $staff) {
            throw new \RuntimeException('User has no staff profile.');
        }

        $hasAssignment = CustomerAssignment::where('customer_id', $customer->id)
            ->where('staff_id', $staff->id)
            ->where('status', 'active')
            ->exists();

        if (! $hasAssignment) {
            throw new \RuntimeException('You are not assigned to this customer.');
        }
    }

    private function validatePriceBookAccess(User $user, PriceBook $priceBook, Customer $customer): void
    {
        $customerType = $customer->customer_type ?? 'personal';
        if (! in_array($priceBook->audience_type->value, [$customerType, 'both'], true)) {
            throw new \InvalidArgumentException('Price book audience type does not match customer.');
        }
        if (! $this->priceBookAccess->canCreateQuotation($user, $priceBook)) {
            throw new \RuntimeException('You do not have permission to use this price book.');
        }
    }

    private function buildItemData(array $items, PriceBook $priceBook): array
    {
        $result = [];
        foreach ($items as $item) {
            $pbi = isset($item['price_book_item_id'])
                ? PriceBookItem::with(['servicePackage.service'])->find($item['price_book_item_id'])
                : null;

            $result[] = [
                'service_id' => $pbi?->servicePackage?->service_id ?? $item['service_id'] ?? null,
                'service_package_id' => $pbi?->service_package_id ?? $item['service_package_id'] ?? null,
                'price_book_item_id' => $pbi?->id,
                'service_code_snapshot' => $pbi?->servicePackage?->service?->service_code ?? $item['service_code_snapshot'] ?? '',
                'service_name_snapshot' => $pbi?->servicePackage?->service?->name ?? $item['service_name_snapshot'] ?? '',
                'package_code_snapshot' => $pbi?->servicePackage?->package_code ?? $item['package_code_snapshot'] ?? null,
                'package_name_snapshot' => $pbi?->servicePackage?->name ?? $item['package_name_snapshot'] ?? '',
                'description_snapshot' => $item['description_snapshot'] ?? $pbi?->description ?? null,
                'scope_snapshot' => $item['scope_snapshot'] ?? $pbi?->scope_override ?? null,
                'terms_snapshot' => $item['terms_snapshot'] ?? $pbi?->terms_override ?? null,
                'unit' => $item['unit'] ?? $pbi?->servicePackage?->unit ?? 'tháng',
                'quantity' => $item['quantity'] ?? ($pbi?->servicePackage?->default_quantity ?? 1),
                'unit_price' => $item['unit_price'] ?? $pbi?->unit_price ?? 0,
                'discount_type' => $item['discount_type'] ?? $pbi?->default_discount_type?->value ?? null,
                'discount_value' => $item['discount_value'] ?? $pbi?->default_discount_value ?? 0,
                'vat_rate' => $item['vat_rate'] ?? $pbi?->vat_rate ?? 10,
            ];

            $this->validateDiscountLimit($pbi, $result[array_key_last($result)]);
        }

        return $result;
    }

    private function validateDiscountLimit(?PriceBookItem $pbi, array $data): void
    {
        if (! $pbi || $pbi->maximum_discount_value === null || (float) $pbi->maximum_discount_value <= 0) {
            return;
        }

        $quantity = (int) ($data['quantity'] ?? 1);
        $unitPrice = (float) ($data['unit_price'] ?? 0);
        $lineSubtotal = $quantity * $unitPrice;

        $discountType = $data['discount_type'] ?? null;
        $discountValue = (float) ($data['discount_value'] ?? 0);

        $discountAmount = match ($discountType) {
            DiscountType::Fixed->value => min($discountValue, $lineSubtotal),
            DiscountType::Percentage->value => $lineSubtotal * min($discountValue, 100) / 100,
            default => 0,
        };

        if (! $this->pricingService->validateDiscountLimit($discountAmount, (float) $pbi->maximum_discount_value)) {
            throw new \InvalidArgumentException(sprintf(
                'Discount exceeds the maximum allowed (%s %s) for the price book item.',
                $pbi->maximum_discount_value,
                $pbi->priceBook?->currency ?? ''
            ));
        }
    }

    private function buildCustomerSnapshot(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'display_name' => $customer->display_name,
            'customer_type' => $customer->customer_type,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
        ];
    }

    private function buildCompanySnapshot(Customer $customer): array
    {
        return [
            'company_name' => $customer->company_name,
            'tax_code' => $customer->tax_code,
            'company_address' => $customer->company_address,
            'legal_representative' => $customer->legal_representative,
            'contact_position' => $customer->contact_position,
            'business_email' => $customer->business_email,
            'business_phone' => $customer->business_phone,
        ];
    }

    private function buildPaymentSnapshot(array $params): array
    {
        $bankAccount = null;
        if (isset($params['bank_account_id']) && filled($params['bank_account_id'])) {
            $bankAccount = BankAccount::find($params['bank_account_id']);
        }

        if (! $bankAccount) {
            $bankAccount = BankAccount::query()
                ->where('status', 'active')
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();
        }

        return [
            'bank_account_id' => $bankAccount?->id,
            'bank_code' => $bankAccount?->bank_code,
            'bank_name' => $bankAccount?->bank_name,
            'account_number' => $bankAccount?->account_number,
            'account_name' => $bankAccount?->account_name,
            'branch_name' => $bankAccount?->branch_name,
            'swift_code' => $bankAccount?->swift_code,
            'qr_provider' => $bankAccount?->qr_provider,
            'qr_template' => $bankAccount?->qr_template,
            'transfer_content' => $params['transfer_content'] ?? null,
        ];
    }

    private function buildTermsSnapshot(PriceBook $priceBook, array $params): array
    {
        return [
            'valid_until' => $params['valid_until'] ?? now()->addDays(30)->toDateString(),
            'scope' => $params['terms_scope'] ?? null,
            'terms' => $params['terms'] ?? null,
            'notes' => $params['notes'] ?? null,
        ];
    }
}
