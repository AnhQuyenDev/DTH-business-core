<?php

namespace App\Mail\Sales;

use App\Models\Sales\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quotation $quotation,
        public string $reason = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Báo giá {$this->quotation->quotation_code} đã bị từ chối",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'sales.emails.quotation-rejected',
            with: [
                'quotation' => $this->quotation,
                'reason' => $this->reason,
                'customer' => $this->quotation->customer,
            ],
        );
    }
}
