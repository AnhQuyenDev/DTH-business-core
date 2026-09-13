<?php

namespace Dth\Marketing\DTO;

final readonly class RevenueSummary
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public string $amount,
        public string $currency = 'VND',
        public ?int $paidCustomers = null,
        public array $metadata = [],
    ) {}
}
