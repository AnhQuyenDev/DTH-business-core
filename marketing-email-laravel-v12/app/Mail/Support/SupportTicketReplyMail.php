<?php

namespace App\Mail\Support;

use App\Models\Support\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupportTicketReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly SupportTicket $ticket,
        public readonly string $messageBody,
    ) {}

    public function build(): self
    {
        return $this
            ->subject('[Ticket '.$this->ticket->ticket_code.'] '.$this->ticket->subject)
            ->view('support.emails.ticket-reply');
    }
}
