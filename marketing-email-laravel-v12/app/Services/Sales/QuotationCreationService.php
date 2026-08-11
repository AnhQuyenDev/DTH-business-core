<?php

namespace App\Services\Sales;

use App\Enums\Crm\CustomerStatus;
use App\Enums\Sales\DiscountType;
use App\Enums\Sales\OpportunityStage;
use App\Enums\Sales\QuotationStatus;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerAssignment;
use App\Models\Sales\BankAccount;
use App\Models\Sales\Opportunity;
use App\Models\Sales\PriceBook;
use App\Models\Sales\PriceBookItem;
use App\Models\Sales\Quotation;
use App\Models\User;
use App\Services\Marketing\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class QuotationCreationService
{
    public function __construct(
        private readonly QuotationCodeGenerator $codeGenerator,
        private readonly QuotationPricingService $pricingService,
        private readonly PriceBookAccessService $priceBookAccess,
        private readonly QuotationStateMachine $stateMachine,
        private readonly AuditLogService $auditLog,
        private readonly QuotationPartySnapshotService $partySnapshot,
    ) {}

    /**
     * Legacy quotation flow for existing Customers.
     * New CRM v2 quotations must use createForOpportunity().
     */
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
                'payment_snapshot' => $this->buildPaymentSnapshot(array_merge($params, [
                    'transfer_content' => $params['transfer_content'] ?? $code,
                ])),
                'terms_snapshot' => $this->buildTermsSnapshot($priceBook, $params),
                'public_token' => $this->codeGenerator->generatePublicToken(),
                'created_by' => $user->id,
            ]);

            $this->saveItems($quotation, $itemData);

            $this->auditLog->log('quotation.created', $quotation, [], $quotation->toArray());

            return $quotation->fresh(['items', 'customer']);
        });
    }

    public function createForOpportunity(
        Opportunity $opportunity,
        User $user,
        PriceBook $priceBook,
        array $items,
        array $params = [],
    ): Quotation {
        $opportunity->loadMissing([
            'company',
            'primaryContact.personalProfile',
            'primaryContact.businessProfile',
            'assignedStaff',
        ]);

        $this->validateOpportunity($opportunity, $user);
        $this->validateOpportunityPriceBook(
            $opportunity,
            $user,
            $priceBook,
        );

        $itemData = $this->buildItemData($items, $priceBook);
        $this->validateOpportunityItems($opportunity, $itemData);
        $totals = $this->pricingService->calculateTotals(
            collect($itemData)
        );

        return DB::transaction(function () use (
            $opportunity,
            $user,
            $priceBook,
            $itemData,
            $totals,
            $params,
        ): Quotation {
            $locked = Opportunity::query()
                ->whereKey($opportunity->id)
                ->lockForUpdate()
                ->firstOrFail();

            $code = $this->codeGenerator->generate();

            $quotation = Quotation::query()->create([
                'quotation_code' => $code,
                'opportunity_id' => $locked->id,
                'company_id' => $locked->company_id,
                'contact_id' => $locked->primary_contact_id,
                'customer_id' => null,
                'assigned_staff_id' => $locked->assigned_staff_id,
                'price_book_id' => $priceBook->id,
                'bank_account_id' => $params['bank_account_id'] ?? null,
                'title' => $params['title']
                    ?? 'Báo giá '.$locked->title,
                'version' => 1,
                'quotation_date' => $params['quotation_date']
                    ?? now()->toDateString(),
                'valid_until' => $params['valid_until']
                    ?? now()->addDays(30)->toDateString(),
                'currency' => $priceBook->currency,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => $totals['tax_total'],
                'grand_total' => $totals['grand_total'],
                'status' => QuotationStatus::Draft,
                'payment_status' => 'unpaid',
                'email_status' => 'unsent',
                'customer_snapshot' => $this->partySnapshot->customerSnapshot($locked),
                'company_snapshot' => $this->partySnapshot->companySnapshot($locked),
                'payment_snapshot' => $this->buildPaymentSnapshot(array_merge($params, [
                    'transfer_content' => $params['transfer_content'] ?? $code,
                ])),
                'terms_snapshot' => $this->buildTermsSnapshot($priceBook, $params),
                'metadata' => array_merge(
                    $params['metadata'] ?? [],
                    [
                        'created_from' => 'opportunity',
                        'opportunity_code' => $locked->opportunity_code,
                    ]
                ),
                'public_token' => $this->codeGenerator->generatePublicToken(),
                'created_by' => $user->id,
            ]);

            $this->saveItems($quotation, $itemData);

            $this->auditLog->log(
                'quotation.created_from_opportunity',
                $quotation,
                [],
                $quotation->toArray(),
            );

            return $quotation->fresh([
                'items',
                'opportunity',
                'company',
                'contact',
                'customer',
            ]);
        });
    }

    public function update(Quotation $quotation, array $items, array $params = []): Quotation
    {
        $quotation->loadMissing(['priceBook', 'opportunity']);
        $itemData = $this->buildItemData($items, $quotation->priceBook);

        if ($quotation->opportunity !== null) {
            $this->validateOpportunityItems($quotation->opportunity, $itemData);
        }

        $totals = $this->pricingService->calculateTotals(collect($itemData));

        return DB::transaction(function () use ($quotation, $itemData, $totals, $params) {
            $quotation->items()->delete();
            $this->saveItems($quotation, $itemData);

            $bankAccountId = $params['bank_account_id']
                ?? $quotation->bank_account_id;

            $paymentParams = array_merge(
                $params,
                [
                    'bank_account_id' => $bankAccountId,
                    'transfer_content' => $params['transfer_content']
                        ?? data_get($quotation->payment_snapshot, 'transfer_content')
                        ?? $quotation->quotation_code,
                ],
            );

            $quotation->update([
                'bank_account_id' => $bankAccountId,
                'payment_snapshot' => $this->buildPaymentSnapshot(
                    $paymentParams
                ),
                'title' => $params['title'] ?? $quotation->title,
                'valid_until' => $params['valid_until'] ?? $quotation->valid_until,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount_total'],
                'tax_total' => $totals['tax_total'],
                'grand_total' => $totals['grand_total'],
            ]);

            $this->auditLog->log('quotation.updated', $quotation, [], $quotation->toArray());

            return $quotation->fresh([
                'items',
                'opportunity',
                'company',
                'contact',
                'customer',
            ]);
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
                'service_product_id' => $item['service_product_id'] ?? null,
                'price_book_item_id' => $item['price_book_item_id'] ?? null,
                'item_type' => $item['item_type'] ?? (($item['service_product_id'] ?? null) ? 'product' : 'package'),
                'service_code_snapshot' => $item['service_code_snapshot'] ?? '',
                'service_name_snapshot' => $item['service_name_snapshot'] ?? '',
                'package_code_snapshot' => $item['package_code_snapshot'] ?? null,
                'package_name_snapshot' => $item['package_name_snapshot'] ?? '',
                'product_code_snapshot' => $item['product_code_snapshot'] ?? null,
                'product_name_snapshot' => $item['product_name_snapshot'] ?? null,
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
        if ($user->isAdmin() || $user->isSalesManager()) {
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
                ? PriceBookItem::with(['servicePackage.service', 'serviceProduct.service', 'priceBook'])
                    ->find($item['price_book_item_id'])
                : null;

            // Every quotation line must come from the selected Price Book.
            // This makes the server authoritative for the sellable, price,
            // VAT and discount policy even if a crafted request bypasses UI.
            if ($pbi === null) {
                throw ValidationException::withMessages([
                    'items' => 'Mỗi dòng báo giá phải chọn một Sản phẩm hoặc Gói dịch vụ có trong Bảng giá.',
                ]);
            }

            if ((int) $pbi->price_book_id !== (int) $priceBook->id) {
                throw ValidationException::withMessages([
                    'items' => 'Gói giá được chọn không thuộc Bảng giá của báo giá.',
                ]);
            }

            $quantity = (int) ($item['quantity']
                ?? ($pbi?->serviceProduct?->default_quantity ?? $pbi?->servicePackage?->default_quantity ?? 1));

            if ($quantity < 1) {
                throw ValidationException::withMessages([
                    'items' => 'Số lượng dịch vụ phải lớn hơn hoặc bằng 1.',
                ]);
            }

            if ($pbi !== null) {
                $this->validateQuantityLimit($pbi, $quantity);
            }

            $result[] = [
                'service_id' => $pbi?->serviceProduct?->service_id ?? $pbi?->servicePackage?->service_id ?? $item['service_id'] ?? null,
                'service_package_id' => $pbi?->service_package_id ?? $item['service_package_id'] ?? null,
                'service_product_id' => $pbi?->service_product_id ?? $item['service_product_id'] ?? null,
                'price_book_item_id' => $pbi?->id,
                'item_type' => $pbi?->service_product_id ? 'product' : 'package',
                'service_code_snapshot' => $pbi?->serviceProduct?->service?->service_code ?? $pbi?->servicePackage?->service?->service_code ?? $item['service_code_snapshot'] ?? '',
                'service_name_snapshot' => $pbi?->serviceProduct?->service?->name ?? $pbi?->servicePackage?->service?->name ?? $item['service_name_snapshot'] ?? '',
                'package_code_snapshot' => $pbi?->servicePackage?->package_code ?? $item['package_code_snapshot'] ?? null,
                // package_name_snapshot remains a generic commercial-line display name for backward-compatible PDF/email templates.
                'package_name_snapshot' => $pbi?->serviceProduct?->name ?? $pbi?->servicePackage?->name ?? $item['package_name_snapshot'] ?? '',
                'product_code_snapshot' => $pbi?->serviceProduct?->product_code ?? $item['product_code_snapshot'] ?? null,
                'product_name_snapshot' => $pbi?->serviceProduct?->name ?? $item['product_name_snapshot'] ?? null,
                'description_snapshot' => $item['description_snapshot'] ?? $pbi?->description ?? null,
                'scope_snapshot' => $pbi?->scope_override ?? $item['scope_snapshot'] ?? null,
                'terms_snapshot' => $pbi?->terms_override ?? $item['terms_snapshot'] ?? null,
                // Price-book-backed commercial fields are server-authoritative.
                // Disabled UI controls are not a security boundary: crafted
                // requests must not be able to override price/VAT/unit/type.
                'unit' => $pbi?->serviceProduct?->unit ?? $pbi?->servicePackage?->unit ?? $item['unit'] ?? 'đơn vị',
                'quantity' => $quantity,
                'unit_price' => $pbi !== null
                    ? (float) $pbi->unit_price
                    : (float) ($item['unit_price'] ?? 0),
                'discount_type' => $pbi !== null
                    ? $pbi->default_discount_type?->value
                    : ($item['discount_type'] ?? null),
                'discount_value' => $item['discount_value']
                    ?? $pbi?->default_discount_value
                    ?? 0,
                'vat_rate' => $pbi !== null
                    ? (float) ($pbi->vat_rate ?? 0)
                    : (float) ($item['vat_rate'] ?? 0),
            ];

            $this->validateDiscountLimit($pbi, $result[array_key_last($result)]);
        }

        return $result;
    }

    private function validateQuantityLimit(
        PriceBookItem $pbi,
        int $quantity,
    ): void {
        $minimum = max(1, (int) ($pbi->minimum_quantity ?? 1));
        $maximum = $pbi->maximum_quantity !== null
            ? (int) $pbi->maximum_quantity
            : null;

        if ($quantity < $minimum || ($maximum !== null && $quantity > $maximum)) {
            $range = $maximum !== null
                ? "{$minimum}-{$maximum}"
                : "từ {$minimum} trở lên";

            throw ValidationException::withMessages([
                'items' => 'Số lượng của gói '
                    .($pbi->serviceProduct?->name ?? $pbi->servicePackage?->name ?? 'sản phẩm/dịch vụ đã chọn')
                    ." phải nằm trong phạm vi {$range}.",
            ]);
        }
    }

    /**
     * Validate commercial lines against the current Opportunity context.
     * Customer needs can evolve during consultation, so service_interest is
     * context only; the selected Price Book is the commercial source of truth.
     */
    private function validateOpportunityItems(
        Opportunity $opportunity,
        array $itemData,
    ): void {
        // Customer needs can evolve during consultation. service_interest is
        // attribution/context only; it must never lock a quotation to one
        // package. The commercial source of truth is the chosen Price Book.
        if ($itemData === []) {
            throw ValidationException::withMessages([
                'items' => 'Báo giá phải có ít nhất một sản phẩm hoặc gói dịch vụ.',
            ]);
        }
    }

    private function validateDiscountLimit(?PriceBookItem $pbi, array $data): void
    {
        if (! $pbi || $pbi->maximum_discount_value === null || (float) $pbi->maximum_discount_value <= 0) {
            return;
        }

        $discountType = $data['discount_type'] ?? null;
        $discountValue = (float) ($data['discount_value'] ?? 0);

        $maximumDiscount = (float) (
            $pbi->maximum_discount_value ?? 0
        );

        if ($maximumDiscount > 0) {
            $exceedsMaximum = match ($discountType) {
                DiscountType::Percentage->value =>
                    $discountValue > $maximumDiscount,

                DiscountType::Fixed->value =>
                    $discountValue > $maximumDiscount,

                default => false,
            };

            if ($exceedsMaximum) {
                $limitLabel = match ($discountType) {
                    DiscountType::Percentage->value =>
                        number_format(
                            $maximumDiscount,
                            2
                        ).'%',

                    DiscountType::Fixed->value =>
                        number_format(
                            $maximumDiscount,
                            0,
                            ',',
                            '.'
                        ).' '.(
                            $pbi->priceBook?->currency ?? 'VND'
                        ),

                    default =>
                        number_format(
                            $maximumDiscount,
                            2
                        ),
                };

                throw ValidationException::withMessages([
                    'items' =>
                        'Mức giảm giá vượt giới hạn tối đa '
                        ."cho phép ({$limitLabel}) của gói "
                        .(
                            $pbi->servicePackage?->name
                            ?? 'dịch vụ đã chọn'
                        )
                        .'.',
                ]);
            }
        }
    }

    private function validateOpportunity(
        Opportunity $opportunity,
        User $user,
    ): void {
        $stage = $opportunity->stage instanceof OpportunityStage
            ? $opportunity->stage
            : OpportunityStage::from((string) $opportunity->stage);

        if (! in_array($stage, [
            OpportunityStage::Qualified,
            OpportunityStage::Proposal,
            OpportunityStage::Negotiation,
        ], true)) {
            throw ValidationException::withMessages([
                'opportunity_id' => __(
                    'validation.opportunity_cannot_create_quotation'
                ),
            ]);
        }

        if ($opportunity->primary_contact_id === null) {
            throw ValidationException::withMessages([
                'opportunity_id' => __(
                    'validation.opportunity_primary_contact_required'
                ),
            ]);
        }

        if (
            ! $user->isSalesStaff()
            || $user->staff?->id === null
            || $opportunity->assigned_staff_id !== $user->staff->id
        ) {
            throw ValidationException::withMessages([
                'opportunity_id' => __(
                    'validation.opportunity_not_assigned_to_user'
                ),
            ]);
        }
    }

    private function validateOpportunityPriceBook(
        Opportunity $opportunity,
        User $user,
        PriceBook $priceBook,
    ): void {
        $partyType = $opportunity->company_id
            ? 'business'
            : 'personal';

        $audience = $priceBook->audience_type instanceof \BackedEnum
            ? $priceBook->audience_type->value
            : (string) $priceBook->audience_type;

        if (! in_array($audience, [$partyType, 'both'], true)) {
            throw ValidationException::withMessages([
                'price_book_id' => __(
                    'validation.price_book_audience_mismatch'
                ),
            ]);
        }

        if (! $this->priceBookAccess->canCreateQuotation(
            $user,
            $priceBook,
        )) {
            throw ValidationException::withMessages([
                'price_book_id' => __(
                    'validation.price_book_not_accessible'
                ),
            ]);
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
