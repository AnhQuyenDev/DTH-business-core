<?php

namespace Dth\Email\Enums;

enum SendingDomainStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Failed = 'failed';
    case Disabled = 'disabled';
}
