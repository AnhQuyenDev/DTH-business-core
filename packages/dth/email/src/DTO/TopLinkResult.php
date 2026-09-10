<?php

namespace Dth\Email\DTO;

use Carbon\CarbonImmutable;

final readonly class TopLinkResult
{
    public function __construct(
        public string $url,
        public int $messagesContainingLink,
        public int $uniqueClickers,
        public int $totalClicks,
        public ?CarbonImmutable $lastClickedAt,
        public float $clickShare,
    ) {}
}
