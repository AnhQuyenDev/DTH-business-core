<?php

namespace App\Mail\Sales;

use App\Models\Sales\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationExpiredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quotation $quotation,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Báo giá {$this->quotation->quotation_code} đã hết hạn",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'sales.emails.quotation-expired',
            with: [
                'quotation' => $this->quotation,
                'customer' => $this->quotation->customer,
            ],
        );
    }
}
