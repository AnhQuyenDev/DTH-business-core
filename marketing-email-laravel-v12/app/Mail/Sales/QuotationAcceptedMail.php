<?php

namespace App\Mail\Sales;

use App\Models\Sales\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationAcceptedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quotation $quotation,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Báo giá {$this->quotation->quotation_code} đã được chấp nhận",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'sales.emails.quotation-accepted',
            with: [
                'quotation' => $this->quotation,
                'customer' => $this->quotation->customer,
                'items' => $this->quotation->items,
            ],
        );
    }
}
