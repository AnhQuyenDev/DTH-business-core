<?php

namespace App\Data\Crm;

use App\Models\Crm\Company;

final readonly class CompanyResolutionResult
{
    private function __construct(
        public ?Company $company,
        public bool $matched,
        public ?int $confidenceScore,
        public ?string $matchedBy,
    ) {}

    public static function matched(
        Company $company,
        int $confidenceScore,
        string $matchedBy,
    ): self {
        return new self(
            company: $company,
            matched: true,
            confidenceScore: $confidenceScore,
            matchedBy: $matchedBy,
        );
    }

    public static function candidate(
        Company $company,
        int $confidenceScore,
        string $matchedBy,
    ): self {
        return new self(
            company: $company,
            matched: false,
            confidenceScore: $confidenceScore,
            matchedBy: $matchedBy,
        );
    }

    public static function notFound(): self
    {
        return new self(
            company: null,
            matched: false,
            confidenceScore: null,
            matchedBy: null,
        );
    }

    public function found(): bool
    {
        return $this->company !== null;
    }

    public function isAutoMatch(): bool
    {
        return $this->matched && $this->confidenceScore !== null;
    }
}
