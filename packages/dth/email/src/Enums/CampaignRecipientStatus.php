<?php

namespace Dth\Email\Enums;

enum CampaignRecipientStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Opened = 'opened';
    case Clicked = 'clicked';

    case Unsubscribed = 'unsubscribed';

    case Failed = 'failed';
    case Bounced = 'bounced';
    case Complained = 'complained';
    case Suppressed = 'suppressed';
}