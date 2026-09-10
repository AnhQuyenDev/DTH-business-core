<?php

namespace Dth\Email\Enums;

enum EmailTemplateStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';
}
