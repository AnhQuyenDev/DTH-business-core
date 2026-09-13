<?php

namespace Dth\Marketing\DTO;

final readonly class AudienceContactData
{
    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public ?string $email = null,
        public ?string $phone = null,
        public string $type = 'personal',
        public array $attributes = [],
        public array $metadata = [],
    ) {}
}
