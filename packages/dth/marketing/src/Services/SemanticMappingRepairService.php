<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Enums\FormAudienceType;
use Dth\Marketing\Enums\FormFieldType;
use Dth\Marketing\Enums\SemanticFieldRole;
use Dth\Marketing\Models\FormField;
use Dth\Marketing\Models\LandingPageSubmission;

final class SemanticMappingRepairService
{
    public function __construct(
        private readonly SemanticFieldResolver $resolver,
        private readonly SubmissionSemanticNormalizer $normalizer,
    ) {}

    /** @return array{fields:int,submissions:int} */
    public function repairAll(): array
    {
        return [
            'fields' => $this->repairFields(),
            'submissions' => $this->repairSubmissions(),
        ];
    }

    public function repairFields(): int
    {
        $updated = 0;

        FormField::query()
            ->with('formTemplate')
            ->orderBy('id')
            ->chunkById(200, function ($fields) use (&$updated): void {
                foreach ($fields as $field) {
                    if (! $field instanceof FormField) {
                        continue;
                    }

                    if ((string) ($field->semantic_source ?? '') === 'manual') {
                        continue;
                    }

                    $template = $field->formTemplate;
                    $audience = $template?->audience_type instanceof FormAudienceType
                        ? $template->audience_type
                        : FormAudienceType::tryFrom((string) ($template?->audience_type ?? ''));

                    $legacyRole = SemanticFieldRole::fromLegacyContactMapping($field->contact_mapping);
                    if ($legacyRole !== null) {
                        $role = $legacyRole;
                        $confidence = 100;
                        $source = 'legacy';
                    } else {
                        $attributes = (array) data_get(
                            (array) ($template?->schema ?? []),
                            'field_attributes.'.(string) $field->field_key,
                            [],
                        );
                        $resolution = $this->resolver->resolve(
                            fieldKey: (string) $field->field_key,
                            label: (string) $field->label,
                            placeholder: $field->placeholder !== null ? (string) $field->placeholder : null,
                            fieldType: $field->field_type instanceof FormFieldType ? $field->field_type : (string) $field->field_type,
                            audienceType: $audience,
                            htmlAttributes: $attributes,
                        );
                        $role = $resolution['role'];
                        $confidence = (int) $resolution['confidence'];
                        $source = $resolution['source'];
                    }

                    if ($role === null) {
                        continue;
                    }

                    $changes = [
                        'semantic_role' => $role->value,
                        'semantic_confidence' => $confidence,
                        'semantic_source' => $source,
                    ];

                    if (blank($field->contact_mapping) && in_array($source, ['auto', 'legacy'], true) && $role->legacyContactMapping() !== null) {
                        $changes['contact_mapping'] = $role->legacyContactMapping();
                    }

                    FormField::query()->whereKey($field->getKey())->update($changes);
                    $updated++;
                }
            });

        return $updated;
    }

    public function repairSubmissions(): int
    {
        $updated = 0;

        LandingPageSubmission::query()
            ->with('formTemplate.fields')
            ->orderBy('id')
            ->chunkById(100, function ($submissions) use (&$updated): void {
                foreach ($submissions as $submission) {
                    if (! $submission instanceof LandingPageSubmission || $submission->formTemplate === null) {
                        continue;
                    }

                    $normalized = $this->normalizer->normalize(
                        $submission->formTemplate,
                        (array) ($submission->data ?? []),
                    );
                    $summary = $this->normalizer->summary($normalized);

                    $changes = ['normalized_data' => $normalized];
                    if (blank($submission->display_name) && filled($summary['display_name'])) {
                        $changes['display_name'] = $summary['display_name'];
                    }
                    if (blank($submission->normalized_email) && filled($summary['email'])) {
                        $changes['normalized_email'] = $summary['email'];
                    }
                    if (blank($submission->normalized_phone) && filled($summary['phone'])) {
                        $changes['normalized_phone'] = $summary['phone'];
                    }
                    if (blank($submission->company_name) && filled($summary['company_name'])) {
                        $changes['company_name'] = $summary['company_name'];
                    }
                    if (blank($submission->service_reference) && filled($summary['service_interest'])) {
                        $changes['service_reference'] = $summary['service_interest'];
                    }

                    $submission->forceFill($changes)->saveQuietly();
                    $updated++;
                }
            });

        return $updated;
    }
}
