<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Contracts\LeadProvider;
use Dth\Marketing\Enums\FormAudienceType;
use Dth\Marketing\Support\UiText;

final class FormMappingRegistry
{
    public function __construct(
        private readonly LeadProvider $leadProvider,
    ) {}

    public function available(): bool
    {
        return $this->leadProvider->available();
    }

    /** @return array<string, string> */
    public function options(?string $audienceType): array
    {
        if (! $this->available()) {
            return [];
        }

        $common = [
            'lead.name' => UiText::get('form.mapping.name', 'Lead / contact name'),
            'lead.email' => UiText::get('form.mapping.email', 'Email'),
            'lead.phone' => UiText::get('form.mapping.phone', 'Phone'),
            'lead.service_interest' => UiText::get('form.mapping.service_interest', 'Service interest'),
        ];

        if ($audienceType === FormAudienceType::Business->value) {
            return $common + [
                'lead.company_name' => UiText::get('form.mapping.company_name', 'Company name'),
                'lead.tax_code' => UiText::get('form.mapping.tax_code', 'Tax code'),
                'lead.position' => UiText::get('form.mapping.position', 'Contact position'),
            ];
        }

        return $common;
    }
}
