<?php

namespace Dth\Email\DTO;

final readonly class EmailInsightReport
{
    /** @param array<int, EmailInsightItem> $items */
    public function __construct(
        public string $summary,
        public array $items,
        public int $score,
    ) {}
}
