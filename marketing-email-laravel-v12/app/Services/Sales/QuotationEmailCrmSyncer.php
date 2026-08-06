<?php

namespace App\Services\Sales;

use App\Enums\Marketing\EmailEventType;
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
        if (
            $quotation->customer_id === null
            && $quotation->opportunity_id === null
        ) {
            return;
        }

        try {
            $this->recordEmailEvent($quotation, $subject, $context, $emailLogId);

            app(QuotationInteractionService::class)->logSent(
                $quotation,
                $quotation->party_email ?? '',
            );
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
        if (
            $quotation->customer_id === null
            && $quotation->opportunity_id === null
        ) {
            return;
        }

        try {
            EmailEvent::create([
                'tracking_token' => (string) Str::uuid(),
                'customer_id' => $quotation->customer_id,
                'contact_id' => $quotation->contact_id,
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

    /**
     * Convert HTML email body to plain text suitable for storage in a
     * CustomerInteraction content field.
     *
     * Ensures no markup or style/script remnants leak into the stored content.
     */
    public static function cleanHtmlForContent(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // Remove <style>, <script> and comment blocks with their content
        $html = preg_replace(['#<style[^>]*>.*?</style>#is', '#<script[^>]*>.*?</script>#is'], '', $html);
        $html = preg_replace('#<!--.*?-->#s', '', $html);

        // strip remaining HTML tags
        $text = strip_tags($html);

        // Remove leftover CSS rules that look like "selector { ... }" (from previously-stripped <style> content)
        $text = preg_replace('/[^{]*\{[^}]*\}\s*/', '', $text);

        // Remove any remaining @media / @-rules
        $text = preg_replace('/@\w+\s*[^;]*;/', '', $text);

        return trim($text);
    }
}
