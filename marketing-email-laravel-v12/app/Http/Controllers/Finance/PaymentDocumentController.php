<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\PaymentReceipt;
use App\Models\Sales\QuotationPaymentNoticeFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentDocumentController extends Controller
{
    public function evidence(
        Request $request,
        QuotationPaymentNoticeFile $file,
    ) {
        $file->loadMissing('paymentNotice.quotation.customer');
        $quotation = $file->paymentNotice?->quotation;

        abort_unless($quotation !== null, 404);

        $user = $request->user();
        $canViewQuotation = $user?->can('view', $quotation) ?? false;
        $canViewCustomer = $quotation->customer !== null
            && ($user?->can('view', $quotation->customer) ?? false);

        abort_unless($canViewQuotation || $canViewCustomer, 403);
        abort_unless(Storage::disk($file->disk)->exists($file->file_path), 404);

        return Storage::disk($file->disk)->response(
            $file->file_path,
            $file->original_name,
            ['Content-Type' => $file->mime_type],
        );
    }

    public function receipt(
        Request $request,
        PaymentReceipt $receipt,
    ) {
        $receipt->loadMissing('payment.quotation.customer');
        $payment = $receipt->payment;
        $quotation = $payment?->quotation;

        abort_unless($payment !== null && $quotation !== null, 404);

        $user = $request->user();
        $canViewQuotation = $user?->can('view', $quotation) ?? false;
        $canViewCustomer = $payment->customer !== null
            && ($user?->can('view', $payment->customer) ?? false);

        abort_unless($canViewQuotation || $canViewCustomer, 403);
        abort_unless(Storage::disk($receipt->disk)->exists($receipt->file_path), 404);

        return Storage::disk($receipt->disk)->response(
            $receipt->file_path,
            $receipt->file_name,
            ['Content-Type' => $receipt->mime_type],
        );
    }
}
