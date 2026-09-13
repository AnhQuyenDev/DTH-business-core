<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Enums\FormAudienceType;
use Dth\Marketing\Enums\FormFieldType;
use Dth\Marketing\Enums\SemanticFieldRole;
use Dth\Marketing\Models\FormField;
use Dth\Marketing\Models\FormTemplate;

final class SubmissionSemanticNormalizer
{
    public function __construct(
        private readonly SemanticFieldResolver $resolver,
    ) {}

    /** @return array<string, mixed> */
    public function normalize(FormTemplate $template, array $data): array
    {
        $template->loadMissing('fields');
        $data = $this->alignDataToFields($template, $data);
        $normalized = [];

        foreach ($template->fields as $field) {
            $role = $this->effectiveRole($field, $template);
            if ($role === null) {
                continue;
            }

            $value = array_key_exists((string) $field->field_key, $data)
                ? $data[(string) $field->field_key]
                : $field->default_value;

            if (! $this->hasValue($value)) {
                continue;
            }

            $path = $role->value;
            if ($this->hasValue(data_get($normalized, $path))) {
                continue;
            }

            data_set($normalized, $path, $this->normalizeValue($role, $value));
        }

        return $normalized;
    }

    /**
     * Preserve the previous lead.* integration envelope while Marketing stores
     * its own module-neutral semantic roles internally.
     *
     * @return array<string, mixed>
     */
    public function legacyMappedValues(FormTemplate $template, array $data, ?array $normalized = null): array
    {
        $template->loadMissing('fields');
        $data = $this->alignDataToFields($template, $data);
        $mapped = [];

        // Explicit historic mappings keep precedence for backward compatibility.
        foreach ($template->fields as $field) {
            $mapping = trim((string) ($field->contact_mapping ?? ''));
            if ($mapping === '') {
                continue;
            }

            $value = $data[(string) $field->field_key] ?? $field->default_value;
            if ($this->hasValue($value)) {
                $mapped[$mapping] = $value;
            }
        }

        $normalized ??= $this->normalize($template, $data);
        foreach (SemanticFieldRole::cases() as $role) {
            $legacy = $role->legacyContactMapping();
            if ($legacy === null || array_key_exists($legacy, $mapped)) {
                continue;
            }

            $value = data_get($normalized, $role->value);
            if ($this->hasValue($value)) {
                $mapped[$legacy] = $value;
            }
        }

        return $mapped;
    }

    /** @return array{display_name:?string,email:?string,phone:?string,company_name:?string,service_interest:?string} */
    public function summary(array $normalized): array
    {
        return [
            'display_name' => $this->scalarOrNull(
                data_get($normalized, SemanticFieldRole::PersonName->value)
                    ?? data_get($normalized, SemanticFieldRole::CompanyRepresentative->value)
                    ?? data_get($normalized, SemanticFieldRole::CompanyName->value),
            ),
            'email' => $this->normalizeEmail(data_get($normalized, SemanticFieldRole::ContactEmail->value)),
            'phone' => $this->normalizePhone(data_get($normalized, SemanticFieldRole::ContactPhone->value)),
            'company_name' => $this->scalarOrNull(data_get($normalized, SemanticFieldRole::CompanyName->value)),
            'service_interest' => $this->scalarOrNull(data_get($normalized, SemanticFieldRole::ServiceInterest->value)),
        ];
    }

    public function effectiveRole(FormField $field, ?FormTemplate $template = null): ?SemanticFieldRole
    {
        $storedRole = $field->semantic_role instanceof SemanticFieldRole
            ? $field->semantic_role
            : SemanticFieldRole::tryFrom((string) ($field->semantic_role ?? ''));
        $source = trim((string) ($field->semantic_source ?? ''));
        $confidence = (int) ($field->semantic_confidence ?? 0);

        if ($source === 'manual' && $storedRole === null) {
            return null;
        }

        if ($storedRole !== null) {
            if ($source === 'manual' || $source === 'legacy' || $source === 'auto' || $confidence >= SemanticFieldResolver::AUTO_THRESHOLD) {
                return $storedRole;
            }
        }

        // Suggested mappings are intentionally not treated as confirmed.
        if ($source === 'suggested') {
            return null;
        }

        $legacyRole = SemanticFieldRole::fromLegacyContactMapping($field->contact_mapping);
        if ($legacyRole !== null) {
            return $legacyRole;
        }

        $template ??= $field->relationLoaded('formTemplate')
            ? $field->formTemplate
            : $field->formTemplate()->first();
        $audience = $template?->audience_type instanceof FormAudienceType
            ? $template->audience_type
            : FormAudienceType::tryFrom((string) ($template?->audience_type ?? ''));

        $resolution = $this->resolver->resolve(
            fieldKey: (string) $field->field_key,
            label: (string) $field->label,
            placeholder: $field->placeholder !== null ? (string) $field->placeholder : null,
            fieldType: $field->field_type instanceof FormFieldType ? $field->field_type : (string) $field->field_type,
            audienceType: $audience,
        );

        return $resolution['confidence'] >= SemanticFieldResolver::AUTO_THRESHOLD
            ? $resolution['role']
            : null;
    }


    /** @return array<string, mixed> */
    private function alignDataToFields(FormTemplate $template, array $data): array
    {
        if (! array_is_list($data)) {
            return $data;
        }

        $aligned = [];
        foreach ($template->fields->values() as $index => $field) {
            if (! array_key_exists($index, $data)) {
                continue;
            }

            $aligned[(string) $field->field_key] = $data[$index];
        }

        return $aligned;
    }

    private function normalizeValue(SemanticFieldRole $role, mixed $value): mixed
    {
        return match ($role) {
            SemanticFieldRole::ContactEmail => $this->normalizeEmail($value),
            SemanticFieldRole::ContactPhone => $this->normalizePhone($value),
            default => is_scalar($value) ? trim((string) $value) : $value,
        };
    }

    private function normalizeEmail(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = strtolower(trim((string) $value));

        return $value !== '' ? mb_substr($value, 0, 255) : null;
    }

    private function normalizePhone(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = preg_replace('/[^0-9+]/', '', trim((string) $value)) ?? '';

        return $value !== '' ? mb_substr($value, 0, 50) : null;
    }

    private function scalarOrNull(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? mb_substr($value, 0, 255) : null;
    }

    private function hasValue(mixed $value): bool
    {
        if (is_array($value)) {
            return $value !== [];
        }

        return $value !== null && trim((string) $value) !== '';
    }
}
