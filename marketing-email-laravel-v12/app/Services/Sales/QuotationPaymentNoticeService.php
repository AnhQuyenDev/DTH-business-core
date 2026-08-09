<?php

namespace App\Services\Sales;

use App\Enums\Sales\PaymentNoticeStatus;
use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\QuotationStatus;
use App\Models\Sales\Quotation;
use App\Models\Sales\QuotationPaymentNotice;
use App\Services\Marketing\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

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

        $files = collect($data['proof_files'] ?? [])
            ->filter(fn (mixed $file): bool => $file instanceof UploadedFile)
            ->values();

        if ($files->isEmpty()) {
            throw ValidationException::withMessages([
                'proof_files' => 'Vui lòng tải lên ít nhất một ảnh hoặc PDF chứng từ chuyển khoản.',
            ]);
        }

        $payerEmail = $this->publicAccess->assertAuthorizedSignerEmail(
            $quotation,
            (string) ($data['payer_email'] ?? ''),
        );

        $declaredAmount = (float) ($data['declared_amount'] ?? 0);
        $grandTotal = (float) $quotation->grand_total;

        // V1 hỗ trợ thanh toán đủ 100%. Partial payment cần ledger trả góp riêng.
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

        $storedFiles = [];
        $evidenceDisk = (string) config('finance.evidence_disk', 'local');

        try {
            return DB::transaction(function () use (
                $quotation,
                $data,
                $request,
                $payerEmail,
                $declaredAmount,
                $files,
                $evidenceDisk,
                &$storedFiles,
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

                /** @var UploadedFile $file */
                foreach ($files as $file) {
                    $sha256 = hash_file('sha256', $file->getRealPath());
                    $path = $file->store(
                        'payments/notices/'.$notice->id,
                        $evidenceDisk,
                    );

                    if (! is_string($path) || $path === '') {
                        throw new \RuntimeException('Không thể lưu chứng từ thanh toán.');
                    }

                    $storedFiles[] = $path;

                    $notice->files()->create([
                        'disk' => $evidenceDisk,
                        'file_path' => $path,
                        'original_name' => substr($file->getClientOriginalName(), 0, 255),
                        'mime_type' => (string) ($file->getMimeType() ?: 'application/octet-stream'),
                        'file_size' => (int) $file->getSize(),
                        'sha256' => $sha256,
                        'uploaded_at' => now(),
                    ]);
                }

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
                        'evidence_count' => $notice->files()->count(),
                        'evidence_sha256' => $notice->files()->pluck('sha256')->all(),
                    ],
                );

                return $notice->fresh('files');
            });
        } catch (Throwable $e) {
            foreach ($storedFiles as $path) {
                Storage::disk($evidenceDisk)->delete($path);
            }

            throw $e;
        }
    }
}
