<?php

namespace Dth\Email\Contracts;

use Dth\Email\Models\EmailMessage;
use Dth\Email\Models\SendingAccount;

interface EmailTransport
{
    public function send(EmailMessage $message, SendingAccount $account): ?string;
}
