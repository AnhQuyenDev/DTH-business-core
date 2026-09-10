<?php

namespace Dth\Email\DTO;

final readonly class EmailInsightItem
{
    public function __construct(
        public string $severity,
        public string $title,
        public string $body,
        public ?string $metric = null,
    ) {}
}
