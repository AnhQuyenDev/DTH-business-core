<?php

namespace App\Services\Finance;

use App\Models\Finance\Payment;
use App\Models\Finance\PaymentAttribution;
use App\Models\Finance\PaymentRevenueLine;
use App\Models\Marketing\Campaign;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\MarketingCampaign;
use App\Models\Sales\Quotation;
use App\Models\Sales\QuotationPaymentNotice;
use App\Models\User;
use App\Services\Marketing\AuditLogService;
use Illuminate\Support\Arr;

final class PaymentLedgerService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function recordVerifiedPayment(
        Quotation $quotation,
        ?QuotationPaymentNotice $notice,
        User $verifiedBy,
    ): Payment {
        $existing = Payment::query()
            ->where('quotation_id', $quotation->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $quotation->loadMissing([
            'items',
            'opportunity.assignedStaff',
            'opportunity.lead.submission.landingPage.marketingCampaign',
            'opportunity.lead.submission.campaign',
        ]);

        $opportunity = $quotation->opportunity;
        $salesStaffId = $quotation->assigned_staff_id
            ?? $opportunity?->assigned_staff_id;

        $netAmount = max(
            0,
            (float) $quotation->grand_total - (float) $quotation->tax_total,
        );

        $payment = Payment::query()->create([
            'quotation_id' => $quotation->id,
            'customer_id' => $quotation->customer_id,
            'opportunity_id' => $quotation->opportunity_id,
            'payment_notice_id' => $notice?->id,
            'bank_account_id' => $quotation->bank_account_id,
            'sales_staff_id' => $salesStaffId,
            'amount' => $quotation->grand_total,
            'net_amount' => $netAmount,
            'tax_amount' => $quotation->tax_total,
            'currency' => $quotation->currency,
            'payment_method' => 'bank_transfer',
            'transfer_reference' => $notice?->transfer_reference,
            'status' => Payment::STATUS_VERIFIED,
            'paid_at' => $quotation->paid_at ?? now(),
            'verified_at' => now(),
            'verified_by_user_id' => $verifiedBy->id,
            'note' => $quotation->payment_note,
            'metadata' => [
                'source' => 'quotation_payment_verification',
                'quotation_code' => $quotation->quotation_code,
                'quotation_version' => $quotation->version,
            ],
        ]);

        $payment->update([
            'payment_code' => sprintf(
                'PAY%s%06d',
                $payment->paid_at?->format('Ym') ?? now()->format('Ym'),
                $payment->id,
            ),
        ]);

        foreach ($quotation->items as $item) {
            PaymentRevenueLine::query()->create([
                'payment_id' => $payment->id,
                'quotation_item_id' => $item->id,
                'service_id' => $item->service_id,
                'service_package_id' => $item->service_package_id,
                'service_code_snapshot' => $item->service_code_snapshot,
                'service_name_snapshot' => $item->service_name_snapshot,
                'package_code_snapshot' => $item->package_code_snapshot,
                'package_name_snapshot' => $item->package_name_snapshot,
                'quantity' => $item->quantity,
                'net_amount' => max(
                    0,
                    (float) $item->line_subtotal - (float) $item->discount_amount,
                ),
                'tax_amount' => $item->vat_amount,
                'gross_amount' => $item->line_total,
            ]);
        }

        $this->snapshotAttribution($payment, $quotation);

        $payment = $payment->fresh([
            'revenueLines',
            'attribution',
        ]);

        $this->auditLog->log(
            'payment.verified_recorded',
            $payment,
            [],
            [
                'payment_code' => $payment->payment_code,
                'quotation_id' => $payment->quotation_id,
                'quotation_code' => $quotation->quotation_code,
                'gross_amount' => (float) $payment->amount,
                'net_amount' => (float) $payment->net_amount,
                'tax_amount' => (float) $payment->tax_amount,
                'verified_by_user_id' => $verifiedBy->id,
                'attribution_model' => $payment->attribution?->attribution_model,
                'utm_source' => $payment->attribution?->utm_source,
                'marketing_campaign_id' => $payment->attribution?->marketing_campaign_id,
                'landing_page_id' => $payment->attribution?->landing_page_id,
            ],
        );

        return $payment;
    }


    public function attachCustomer(Payment $payment, ?int $customerId): void
    {
        if ($customerId === null || $payment->customer_id === $customerId) {
            return;
        }

        $payment->update(['customer_id' => $customerId]);
    }

    private function snapshotAttribution(
        Payment $payment,
        Quotation $quotation,
    ): void {
        $lead = $quotation->opportunity?->lead;
        $submission = $lead?->submission;
        $metadata = is_array($lead?->metadata) ? $lead->metadata : [];
        $attribution = Arr::wrap(data_get($metadata, 'attribution', []));

        $landingPageId = $submission?->landing_page_id
            ?? $this->existingKey(LandingPage::class, data_get($metadata, 'landing_page_id'));
        $marketingCampaignId = $submission?->marketing_campaign_id
            ?? $this->existingKey(MarketingCampaign::class, data_get($metadata, 'marketing_campaign_id'));
        $emailCampaignId = $submission?->campaign_id
            ?? $this->existingKey(Campaign::class, data_get($metadata, 'campaign_id'));

        PaymentAttribution::query()->create([
            'payment_id' => $payment->id,
            'lead_id' => $lead?->id,
            'landing_page_submission_id' => $submission?->id,
            'landing_page_id' => $landingPageId,
            'marketing_campaign_id' => $marketingCampaignId,
            'email_campaign_id' => $emailCampaignId,
            'acquisition_source' => $lead?->source
                ?: ($submission !== null ? 'landing_page' : null),
            'utm_source' => $submission?->utm_source
                ?? data_get($attribution, 'utm_source'),
            'utm_medium' => $submission?->utm_medium
                ?? data_get($attribution, 'utm_medium'),
            'utm_campaign' => $submission?->utm_campaign
                ?? data_get($attribution, 'utm_campaign'),
            'utm_content' => $submission?->utm_content
                ?? data_get($attribution, 'utm_content'),
            'utm_term' => $submission?->utm_term
                ?? data_get($attribution, 'utm_term'),
            'referrer' => $submission?->referrer
                ?? data_get($attribution, 'referrer'),
            'attribution_model' => 'lead_origin',
            'weight' => 1,
            'attribution_snapshot' => [
                'lead_code' => $lead?->lead_code,
                'lead_source' => $lead?->source,
                'lead_source_detail' => $lead?->source_detail,
                'landing_page_name' => $submission?->landingPage?->name
                    ?? data_get($metadata, 'landing_page_name'),
                'marketing_campaign_name' => $submission?->landingPage?->marketingCampaign?->name
                    ?? data_get($metadata, 'marketing_campaign_name'),
                'marketing_campaign_budget' => $submission?->landingPage?->marketingCampaign?->budget,
                'email_campaign_name' => $submission?->campaign?->name,
                'captured_at' => data_get($metadata, 'captured_at')
                    ?? $submission?->submitted_at?->toIso8601String(),
                'service_context' => data_get($metadata, 'service_context', []),
            ],
        ]);
    }
    /**
     * Metadata snapshots can outlive mutable master records. Never allow a
     * stale historical ID to make authoritative Payment creation fail a FK.
     * The human-readable value is still preserved in attribution_snapshot.
     */
    private function existingKey(string $modelClass, mixed $value): ?int
    {
        if (! is_numeric($value) || (int) $value <= 0) {
            return null;
        }

        $id = (int) $value;

        return $modelClass::query()->whereKey($id)->exists() ? $id : null;
    }

}
