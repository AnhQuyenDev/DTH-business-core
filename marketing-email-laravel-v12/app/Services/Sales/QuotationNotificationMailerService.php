<?php

namespace App\Services\Sales;

use App\Models\Marketing\SendingAccount;
use App\Models\Sales\Quotation;
use App\Services\Marketing\SendingAccountMailerService;
use Illuminate\Mail\Mailable;

final class QuotationNotificationMailerService
{
    public function __construct(
        private readonly QuotationSendingAccountResolver $resolver,
        private readonly SendingAccountMailerService $mailer,
    ) {}

    public function send(
        Quotation $quotation,
        string $recipientEmail,
        Mailable $mailable,
    ): void {
        $account = $this->resolveAccount($quotation);

        $this->mailer->send(
            account: $account,
            recipientEmail: $recipientEmail,
            mailable: $mailable,
        );
    }

    /**
     * Prefer the exact account used to send the quotation itself. This keeps
     * follow-up messages on the same SMTP identity even if the department has
     * multiple active sending accounts. Fall back to the normal resolver for
     * legacy quotations that do not yet have an email log snapshot.
     */
    private function resolveAccount(Quotation $quotation): SendingAccount
    {
        $lastAccount = $quotation->emailLogs()
            ->with('sendingAccount')
            ->whereNotNull('sending_account_id')
            ->latest('id')
            ->first()
            ?->sendingAccount;

        if ($lastAccount && (string) $lastAccount->status === 'active') {
            return $lastAccount;
        }

        return $this->resolver->resolve($quotation);
    }
}
