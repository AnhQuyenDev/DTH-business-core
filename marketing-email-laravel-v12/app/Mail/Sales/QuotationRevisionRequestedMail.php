<?php

namespace App\Mail\Sales;

use App\Models\Sales\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationRevisionRequestedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quotation $quotation,
        public string $reason,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Yêu cầu chỉnh sửa báo giá {$this->quotation->quotation_code}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'sales.emails.quotation-revision-requested',
            with: [
                'quotation' => $this->quotation,
                'reason' => $this->reason,
                'customer' => $this->quotation->customer,
            ],
        );
    }
}
