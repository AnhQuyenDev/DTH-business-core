<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MarketingCampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{disk?: string, path: string, name?: string, mime?: string|null}>  $attachments
     */
    public function __construct(
        public string $subjectLine,
        public ?string $preheader,
        public string $htmlBody,
        public ?string $textBody = null,
        public ?string $fromAddress = null,
        public ?string $fromName = null,
        array $attachments = [],
    ) {
        foreach ($attachments as $attachment) {
            $this->attachFromStorageDisk(
                $attachment['disk'] ?? 'public',
                $attachment['path'],
                $attachment['name'] ?? null,
                ['mime' => $attachment['mime'] ?? null],
            );
        }
    }

    public function envelope(): Envelope
    {
        $from = null;
        if (filled($this->fromAddress)) {
            $from = new Address($this->fromAddress, $this->fromName ?? config('mail.from.name', ''));
        }

        return new Envelope(
            from: $from,
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->htmlBody,
        );
    }
}
