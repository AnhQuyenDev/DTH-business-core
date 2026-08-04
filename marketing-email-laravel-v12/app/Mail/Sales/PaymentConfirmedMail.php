<?php

namespace App\Mail\Sales;

use App\Models\Sales\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quotation $quotation,
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
            ],
        );
    }
}
