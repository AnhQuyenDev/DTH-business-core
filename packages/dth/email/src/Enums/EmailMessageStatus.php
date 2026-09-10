<?php

namespace Dth\Email\Enums;

enum EmailMessageStatus: string
{
    case Queued = 'queued';
    case Sending = 'sending';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Bounced = 'bounced';
    case Complained = 'complained';
    case Suppressed = 'suppressed';
}