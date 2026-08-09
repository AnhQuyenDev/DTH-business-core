<?php

namespace App\Services\Finance;

use App\Models\Finance\Payment;
use App\Models\Finance\PaymentReceipt;
use App\Services\Marketing\AuditLogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

final class PaymentReceiptService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function ensureGenerated(Payment $payment): PaymentReceipt
    {
        $existing = $payment->receipt()->first();

        if ($existing !== null && Storage::disk($existing->disk)->exists($existing->file_path)) {
            return $existing;
        }

        // A receipt is an immutable accounting snapshot. If its database row
        // already exists but the physical PDF is missing, rebuild from the
        // stored snapshot instead of current mutable company/customer data.
        if ($existing !== null && is_array($existing->snapshot) && $existing->snapshot !== []) {
            $receiptCode = $existing->receipt_code;
            $snapshot = $existing->snapshot;
            $disk = $existing->disk ?: 'local';
            $fileName = $existing->file_name ?: $receiptCode.'.pdf';
            $filePath = $existing->file_path ?: 'payments/receipts/'.$payment->id.'/'.$fileName;
        } else {
            $payment->loadMissing([
                'quotation',
                'customer',
                'bankAccount',
                'verifiedBy',
                'paymentNotice',
            ]);

            $receiptCode = sprintf(
                'RCPT%s%06d',
                $payment->paid_at?->format('Ym') ?? now()->format('Ym'),
                $payment->id,
            );

            $snapshot = $this->buildSnapshot($payment, $receiptCode);
            $disk = (string) config('finance.receipt_disk', 'local');
            $fileName = $receiptCode.'.pdf';
            $filePath = 'payments/receipts/'.$payment->id.'/'.$fileName;
        }

        $pdf = Pdf::loadView('finance.payment-receipt-pdf', [
            'snapshot' => $snapshot,
        ]);
        $pdf->setPaper('a4');
        $pdf->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => false,
            'defaultMediaType' => 'print',
        ]);

        $pdfBytes = $pdf->output();

        if (! Storage::disk($disk)->put($filePath, $pdfBytes)) {
            throw new \RuntimeException('Không thể lưu biên lai thanh toán.');
        }

        $receipt = PaymentReceipt::query()->updateOrCreate(
            ['payment_id' => $payment->id],
            [
                'receipt_code' => $receiptCode,
                'disk' => $disk,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'mime_type' => 'application/pdf',
                'sha256' => hash('sha256', $pdfBytes),
                'generated_at' => $existing?->generated_at ?? now(),
                'snapshot' => $snapshot,
            ],
        );

        $this->auditLog->log(
            $existing === null ? 'payment.receipt_generated' : 'payment.receipt_repaired',
            $payment,
            [],
            [
                'receipt_id' => $receipt->id,
                'receipt_code' => $receiptCode,
                'sha256' => $receipt->sha256,
                'immutable_snapshot_reused' => $existing !== null,
            ],
        );

        return $receipt;
    }

    /** @return array<string, mixed> */
    private function buildSnapshot(Payment $payment, string $receiptCode): array
    {
        return [
            'receipt_code' => $receiptCode,
            'company' => [
                'name' => company_name(),
                'address' => company_address(),
                'phone' => company_phone(),
                'email' => company_email(),
                'tax_code' => company_tax_code(),
                'logo_data_uri' => company_logo_data_uri(),
            ],
            'customer' => [
                'name' => $payment->customer?->display_name
                    ?? $payment->quotation?->party_display_name,
                'email' => $payment->customer?->email
                    ?? $payment->quotation?->party_email,
                'code' => $payment->customer?->customer_code,
            ],
            'quotation' => [
                'code' => $payment->quotation?->quotation_code,
                'version' => $payment->quotation?->version,
                'title' => $payment->quotation?->title,
            ],
            'payment' => [
                'payment_code' => $payment->payment_code,
                'amount' => (float) $payment->amount,
                'net_amount' => (float) $payment->net_amount,
                'tax_amount' => (float) $payment->tax_amount,
                'currency' => $payment->currency,
                'method' => $payment->payment_method,
                'transfer_reference' => $payment->transfer_reference,
                'paid_at' => $payment->paid_at?->toIso8601String(),
                'verified_at' => $payment->verified_at?->toIso8601String(),
                'verified_by' => $payment->verifiedBy?->name,
            ],
            'bank' => [
                'bank_name' => $payment->bankAccount?->bank_name,
                'account_number' => $payment->bankAccount?->account_number,
                'account_name' => $payment->bankAccount?->account_name,
            ],
        ];
    }
}
