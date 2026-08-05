<?php

namespace App\Services\Marketing;

use App\Mail\MarketingCampaignMail;
use App\Models\Marketing\SendingAccount;
use Illuminate\Support\Facades\Mail;

class SendingAccountService
{
    public function sendTestEmail(SendingAccount $account, string $toEmail, string $subject, string $body): void
    {
        Mail::to($toEmail)->send(new MarketingCampaignMail(
            subjectLine: $subject,
            preheader: null,
            htmlBody: $body,
            textBody: strip_tags($body),
            fromAddress: filled($account->from_email) ? $account->from_email : null,
            fromName: filled($account->from_name) ? $account->from_name : null,
        ));
    }
}
