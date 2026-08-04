<?php

namespace App\Services\Sales;

use App\Enums\Marketing\EmailEventType;
use App\Models\Crm\CustomerInteraction;
use App\Models\Marketing\EmailEvent;
use App\Models\Sales\Quotation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class QuotationEmailCrmSyncer
{
    /**
     * Record a quotation-related email in the CRM so it shows up in customer
     * care email history / timeline and the customer's interaction tab.
     */
    public function recordEmail(
        Quotation $quotation,
        string $subject,
        ?string $rawContent = null,
        string $context = 'quotation',
        ?int $emailLogId = null,
        ?int $staffId = null,
    ): void {
        if (!$quotation->customer) {
            return;
        }

        try {
            $this->recordEmailEvent($quotation, $subject, $context, $emailLogId);

            CustomerInteraction::create([
                'customer_id' => $quotation->customer_id,
                'staff_id' => $staffId ?? $quotation->assigned_staff_id,
                'interaction_type' => 'email',
                'subject' => $subject,
                'content' => Str::limit(strip_tags($rawContent ?? ''), 1000),
                'outcome' => __('page.customer_care.email_outcome'),
                'status' => 'completed',
                'interaction_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('QuotationEmailCrmSyncer: failed to sync email to CRM', [
                'quotation_code' => $quotation->quotation_code,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Record only the sent EmailEvent (used when an interaction already exists).
     */
    public function recordEmailEvent(
        Quotation $quotation,
        string $subject,
        string $context = 'quotation',
        ?int $emailLogId = null,
    ): void {
        $customer = $quotation->customer;

        if (!$customer) {
            return;
        }

        try {
            EmailEvent::create([
                'tracking_token' => (string) Str::uuid(),
                'customer_id' => $customer->id,
                'contact_id' => $customer->contact_id,
                'event_type' => EmailEventType::Sent->value,
                'event_payload' => [
                    'subject' => $subject,
                    'quotation_code' => $quotation->quotation_code,
                    'context' => $context,
                    'email_log_id' => $emailLogId,
                ],
                'occurred_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('QuotationEmailCrmSyncer: failed to record email event', [
                'quotation_code' => $quotation->quotation_code,
                'error' => $e->getMessage(),
            ]);
        }
    }
}