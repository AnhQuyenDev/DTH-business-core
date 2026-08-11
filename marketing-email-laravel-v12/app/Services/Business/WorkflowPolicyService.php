<?php

namespace App\Services\Business;

use App\Models\CompanySetting;

final class WorkflowPolicyService
{
    private ?CompanySetting $settings = null;

    public function quotationApprovalMode(): string
    {
        return (string) ($this->settings()->quotation_approval_mode
            ?: config('v1_workflow.quotation_approval.mode', 'amount_or_discount'));
    }

    public function quotationApprovalAmountThreshold(): float
    {
        $value = $this->settings()->quotation_approval_amount_threshold;

        return max(0, (float) ($value ?? config('v1_workflow.quotation_approval.amount_threshold', 20000000)));
    }

    public function quotationApprovalDiscountThresholdPercent(): float
    {
        $value = $this->settings()->quotation_approval_discount_threshold_percent;

        return max(0, (float) ($value ?? config('v1_workflow.quotation_approval.discount_threshold_percent', 10)));
    }

    public function quotationConfirmationMode(): string
    {
        $mode = (string) ($this->settings()->quotation_confirmation_mode
            ?: config('v1_workflow.customer_confirmation.mode', 'click'));

        return in_array($mode, ['click', 'otp'], true) ? $mode : 'click';
    }

    public function usesOtpConfirmation(): bool
    {
        return $this->quotationConfirmationMode() === 'otp';
    }

    public function paymentEvidenceRequired(): bool
    {
        $settings = $this->settings();

        if ($settings->payment_evidence_required !== null) {
            return (bool) $settings->payment_evidence_required;
        }

        return (bool) config('v1_workflow.payment_notice.evidence_required', false);
    }

    public function supportTicketsEnabled(): bool
    {
        $settings = $this->settings();

        if ($settings->support_tickets_enabled !== null) {
            return (bool) $settings->support_tickets_enabled;
        }

        return true;
    }

    /** @return array<string,mixed> */
    public function snapshot(): array
    {
        return [
            'quotation_approval' => [
                'mode' => $this->quotationApprovalMode(),
                'amount_threshold' => $this->quotationApprovalAmountThreshold(),
                'discount_threshold_percent' => $this->quotationApprovalDiscountThresholdPercent(),
            ],
            'customer_confirmation' => [
                'mode' => $this->quotationConfirmationMode(),
            ],
            'payment_notice' => [
                'evidence_required' => $this->paymentEvidenceRequired(),
            ],
            'support' => [
                'tickets_enabled' => $this->supportTicketsEnabled(),
            ],
        ];
    }

    private function settings(): CompanySetting
    {
        return $this->settings ??= CompanySetting::firstOrCreateDefault();
    }
}
