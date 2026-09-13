<?php

namespace Dth\Marketing\DTO;

final readonly class CatalogServiceData
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public string $reference,
        public string $name,
        public ?string $code = null,
        public bool $active = true,
        public array $metadata = [],
    ) {}
}
