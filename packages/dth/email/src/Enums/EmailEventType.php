<?php

namespace Dth\Email\Enums;

enum EmailEventType: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Opened = 'opened';
    case Clicked = 'clicked';
    case Bounced = 'bounced';
    case Complained = 'complained';
    case Unsubscribed = 'unsubscribed';
    case Failed = 'failed';
    case Suppressed = 'suppressed';
}
