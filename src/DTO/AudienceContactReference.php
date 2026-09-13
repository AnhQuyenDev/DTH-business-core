<?php

namespace Dth\Marketing\DTO;

final readonly class AudienceContactReference
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public string $reference,
        public ?string $displayName = null,
        public array $metadata = [],
    ) {}
}
