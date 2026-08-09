<?php

namespace App\Mail\Sales;

use App\Models\Finance\Payment;
use App\Models\Sales\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quotation $quotation,
        public Payment $payment,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Xác nhận thanh toán báo giá {$this->quotation->quotation_code}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'sales.emails.payment-confirmed',
            with: [
                'quotation' => $this->quotation,
                'customer' => $this->quotation->customer,
                'payment' => $this->payment,
                'receipt' => $this->payment->receipt,
            ],
        );
    }

    public function attachments(): array
    {
        $receipt = $this->payment->receipt;

        if ($receipt === null) {
            return [];
        }

        return [
            Attachment::fromStorageDisk($receipt->disk, $receipt->file_path)
                ->as($receipt->file_name)
                ->withMime($receipt->mime_type),
        ];
    }
}
