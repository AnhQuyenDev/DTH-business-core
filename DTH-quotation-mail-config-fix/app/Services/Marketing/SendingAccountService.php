<?php

namespace App\Services\Marketing;

use App\Mail\MarketingCampaignMail;
use App\Models\Marketing\SendingAccount;

class SendingAccountService
{
    public function __construct(
        private readonly SendingAccountMailerService $mailer,
    ) {}

    public function sendTestEmail(
        SendingAccount $account,
        string $toEmail,
        string $subject,
        string $body,
    ): void {
        $this->mailer->send(
            account: $account,
            recipientEmail: $toEmail,
            mailable: new MarketingCampaignMail(
                subjectLine: $subject,
                preheader: null,
                htmlBody: $body,
                textBody: strip_tags($body),
                fromAddress: $account->from_email,
                fromName: $account->from_name,
            ),
        );
    }
}
