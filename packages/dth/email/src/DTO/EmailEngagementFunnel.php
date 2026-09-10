<?php

namespace Dth\Email\DTO;

final readonly class EmailEngagementFunnel
{
    public function __construct(
        public int $recipients,
        public int $sent,
        public int $opened,
        public int $clicked,
        public int $unsubscribed,
    ) {}
}
