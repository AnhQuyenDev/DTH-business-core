<?php

namespace App\Services\Billing;

use App\Contracts\Billing\ElectronicInvoiceProvider;
use App\Models\Finance\Payment;
use Carbon\CarbonImmutable;

/**
 * Provider-neutral e-invoice orchestration seam.
 *
 * V1 keeps auto issue disabled. Future adapters only need to implement the
 * provider contract + bind it in the service container; the Paid workflow does
 * not need to be rewritten.
 */
final class ElectronicInvoiceService
{
    public function __construct(
        private readonly ElectronicInvoiceProvider $provider,
    ) {}

    public function issueIfEnabled(Payment $payment): Payment
    {
        if (! (bool) config('invoicing.auto_issue_on_paid', false)) {
            return $payment;
        }

        if ((string) $payment->invoice_status === 'issued') {
            return $payment;
        }

        $result = $this->provider->issueForPayment($payment);
        if ($result === null) {
            return $payment;
        }

        $issuedAt = isset($result['issued_at']) && $result['issued_at'] !== ''
            ? CarbonImmutable::parse((string) $result['issued_at'])
            : now();

        $payment->update([
            'invoice_status' => 'issued',
            'invoice_provider' => $result['provider'] ?? config('invoicing.provider'),
            'external_invoice_id' => $result['external_id'] ?? null,
            'invoice_number' => $result['number'] ?? null,
            'invoice_url' => $result['url'] ?? null,
            'invoice_issued_at' => $issuedAt,
        ]);

        return $payment->fresh();
    }
}
