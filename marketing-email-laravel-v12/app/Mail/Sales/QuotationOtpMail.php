<?php

namespace App\Mail\Sales;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotationOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $quotationCode,
        public string $otp,
        public int $expiresInMinutes = 5,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('sales.email.otp_subject', ['code' => $this->quotationCode]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'sales.emails.quotation-otp',
        );
    }
}
