<?php

namespace Dth\Email\Enums;

enum SuppressionReason: string
{
    case Unsubscribe = 'unsubscribe';
    case Bounce = 'bounce';
    case Complaint = 'complaint';
    case Manual = 'manual';
}
