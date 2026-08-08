<?php

namespace App\Services\Sales;

use App\Enums\Sales\PaymentNoticeStatus;
use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\QuotationStatus;
use App\Models\Sales\Quotation;
use App\Models\Sales\QuotationPaymentNotice;
use App\Services\Marketing\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class QuotationPaymentNoticeService
{
    public function __construct(
        private readonly QuotationPublicAccessService $publicAccess,
        private readonly AuditLogService $auditLog,
    ) {}

    public function submit(
        Quotation $quotation,
        array $data,
        Request $request,
    ): QuotationPaymentNotice {
        if ($quotation->status !== QuotationStatus::Accepted) {
            throw ValidationException::withMessages([
                'payment' => 'Chỉ báo giá đã được khách hàng chấp nhận mới được thông báo thanh toán.',
            ]);
        }

        if ($quotation->payment_status === PaymentStatus::Paid) {
            throw ValidationException::withMessages([
                'payment' => 'Báo giá này đã được xác nhận thanh toán.',
            ]);
        }

        $payerEmail = $this->publicAccess->assertAuthorizedSignerEmail(
            $quotation,
            (string) ($data['payer_email'] ?? ''),
        );

        $declaredAmount = (float) ($data['declared_amount'] ?? 0);
        $grandTotal = (float) $quotation->grand_total;

        // Round 2 deliberately supports full-payment confirmation only.
        // Partial payments need a real installment/payment ledger, not just a
        // status flag, otherwise Finance can accidentally close an underpaid quote.
        if ($declaredAmount <= 0 || abs($declaredAmount - $grandTotal) > 0.5) {
            throw ValidationException::withMessages([
                'declared_amount' => 'Số tiền thông báo phải đúng bằng tổng giá trị cần thanh toán của báo giá.',
            ]);
        }

        $existing = $quotation->paymentNotices()
            ->where('status', PaymentNoticeStatus::Pending->value)
            ->latest('id')
            ->first();

        if ($existing !== null) {
            throw ValidationException::withMessages([
                'payment' => 'Đã có một thông báo thanh toán đang chờ bộ phận Tài chính đối soát.',
            ]);
        }

        return DB::transaction(function () use (
            $quotation,
            $data,
            $request,
            $payerEmail,
            $declaredAmount,
        ): QuotationPaymentNotice {
            $notice = $quotation->paymentNotices()->create([
                'status' => PaymentNoticeStatus::Pending->value,
                'payer_name' => trim((string) $data['payer_name']),
                'payer_email' => $payerEmail,
                'declared_amount' => $declaredAmount,
                'transfer_reference' => filled($data['transfer_reference'] ?? null)
                    ? trim((string) $data['transfer_reference'])
                    : null,
                'note' => filled($data['note'] ?? null)
                    ? trim((string) $data['note'])
                    : null,
                'submitted_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            ]);

            if ($quotation->payment_status !== PaymentStatus::PendingVerification) {
                $quotation->update([
                    'payment_status' => PaymentStatus::PendingVerification->value,
                ]);
            }

            $this->auditLog->log(
                'quotation.payment_notice_submitted',
                $quotation,
                [],
                [
                    'payment_notice_id' => $notice->id,
                    'declared_amount' => $declaredAmount,
                    'payer_email' => $payerEmail,
                ],
            );

            return $notice;
        });
    }
}
