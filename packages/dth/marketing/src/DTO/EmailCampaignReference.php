<?php

namespace Dth\Marketing\DTO;

final readonly class EmailCampaignReference
{
    /** @param array<string, mixed> $metrics */
    public function __construct(
        public string $reference,
        public string $name,
        public ?string $status = null,
        public ?string $adminUrl = null,
        public array $metrics = [],
    ) {}
}
