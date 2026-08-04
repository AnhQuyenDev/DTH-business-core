<?php

namespace App\DTO\Tax;

use App\Enums\Crm\TaxVerificationStatus;

final readonly class TaxVerificationResult
{
    public function __construct(
        public TaxVerificationStatus $status,
        public ?string $companyName = null,
        public ?string $companyAddress = null,
        public ?string $legalName = null,
        public ?array $rawData = null,
        public ?string $message = null,
    ) {}
}
