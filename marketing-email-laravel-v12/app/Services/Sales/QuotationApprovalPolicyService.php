<?php

namespace App\Services\Sales;

use App\Models\Sales\Quotation;
use App\Services\Business\WorkflowPolicyService;

final class QuotationApprovalPolicyService
{
    public function __construct(
        private readonly WorkflowPolicyService $workflowPolicy,
    ) {}

    /**
     * @return array{required:bool,mode:string,reason:string,amount:float,discount_percent:float,amount_threshold:float,discount_threshold_percent:float}
     */
    public function evaluate(Quotation $quotation): array
    {
        $quotation->loadMissing('items');

        $mode = $this->workflowPolicy->quotationApprovalMode();
        $amountThreshold = $this->workflowPolicy->quotationApprovalAmountThreshold();
        $discountThreshold = $this->workflowPolicy->quotationApprovalDiscountThresholdPercent();
        $amount = (float) $quotation->grand_total;

        $grossBeforeDiscount = (float) $quotation->subtotal;
        $discountPercent = $grossBeforeDiscount > 0
            ? ((float) $quotation->discount_total / $grossBeforeDiscount) * 100
            : 0.0;

        $amountReached = $amountThreshold > 0 && $amount >= $amountThreshold;
        $discountReached = $discountThreshold > 0 && $discountPercent >= $discountThreshold;

        $required = match ($mode) {
            'none' => false,
            'always' => true,
            'amount_threshold' => $amountReached,
            'discount_threshold' => $discountReached,
            'amount_or_discount' => $amountReached || $discountReached,
            default => true,
        };

        $reason = match (true) {
            $mode === 'none' => 'Không yêu cầu duyệt theo chính sách V1.',
            $mode === 'always' => 'Mọi báo giá đều yêu cầu duyệt.',
            $amountReached && $discountReached => 'Giá trị và mức giảm giá đều vượt ngưỡng duyệt.',
            $amountReached => 'Giá trị báo giá đạt ngưỡng cần duyệt.',
            $discountReached => 'Mức giảm giá đạt ngưỡng cần duyệt.',
            default => 'Báo giá nằm trong hạn mức tự phê duyệt theo chính sách.',
        };

        return [
            'required' => $required,
            'mode' => $mode,
            'reason' => $reason,
            'amount' => $amount,
            'discount_percent' => round($discountPercent, 4),
            'amount_threshold' => $amountThreshold,
            'discount_threshold_percent' => $discountThreshold,
        ];
    }
}
