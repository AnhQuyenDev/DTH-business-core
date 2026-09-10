<?php

namespace Dth\Email\Enums;

enum SendingAccountStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Error = 'error';
}
