<?php

namespace Dth\Marketing\DTO;

final readonly class LeadIntakeData
{
    /**
     * @param array<int, array<string, mixed>> $formAnswers
     * @param array<string, mixed> $attribution
     * @param array<string, mixed> $serviceContext
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $submissionReference,
        public ?string $contactReference = null,
        public ?string $companyReference = null,
        public ?string $serviceReference = null,
        public ?string $serviceLabel = null,
        public ?string $marketingCampaignReference = null,
        public ?string $landingPageReference = null,
        public array $formAnswers = [],
        public array $attribution = [],
        public array $serviceContext = [],
        public array $metadata = [],
    ) {}
}
