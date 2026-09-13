<?php

namespace Dth\Marketing\DTO;

final readonly class LeadReference
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public string $reference,
        public ?string $code = null,
        public ?string $status = null,
        public array $metadata = [],
    ) {}
}
