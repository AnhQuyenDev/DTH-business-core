<?php

namespace Dth\Marketing\DTO;

final readonly class CatalogPackageData
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public string $reference,
        public string $serviceReference,
        public string $name,
        public ?string $code = null,
        public bool $active = true,
        public array $metadata = [],
    ) {}
}
