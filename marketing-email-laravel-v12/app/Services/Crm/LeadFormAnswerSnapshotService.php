<?php

namespace App\Services\Crm;

use App\Models\Marketing\FormTemplate;

final class LeadFormAnswerSnapshotService
{
    /**
     * @return array<int, array{
     *     key: string,
     *     label: string,
     *     type: string,
     *     value: mixed,
     *     display_value: string,
     *     mapping: ?string,
     *     sort_order: int
     * }>
     */
    public function build(
        ?FormTemplate $formTemplate,
        array $validatedData,
        array $optionOverrides = [],
    ): array {
        if ($formTemplate === null) {
            return [];
        }

        $formTemplate->loadMissing('fields');

        return $formTemplate->fields
            ->filter(function ($field) use ($validatedData): bool {
                if (! array_key_exists($field->field_key, $validatedData)) {
                    return false;
                }

                $type = $field->field_type?->value
                    ?? (string) $field->field_type;

                if ($type === 'hidden') {
                    return false;
                }

                $value = $validatedData[$field->field_key];

                if ($value === null || $value === '' || $value === []) {
                    return false;
                }

                $mapping = (string) ($field->contact_mapping ?? '');

                if (in_array($mapping, [
                    'lead.service_interest',
                    'personal.service_interest',
                    'business.service_interest',
                ], true)) {
                    return true;
                }

                if (
                    str_starts_with($mapping, 'personal.')
                    || str_starts_with($mapping, 'business.')
                    || str_starts_with($mapping, 'custom_field:')
                ) {
                    return false;
                }

                return $mapping === '' || str_starts_with($mapping, 'lead.');
            })
            ->sortBy(fn ($field): array => [
                (int) ($field->position ?? 0),
                (int) ($field->sort_order ?? 0),
                (int) $field->id,
            ])
            ->map(function ($field) use (
                $validatedData,
                $optionOverrides,
            ): array {
                $value = $validatedData[$field->field_key];

                return [
                    'key' => (string) $field->field_key,
                    'label' => (string) $field->label,
                    'type' => $field->field_type?->value
                        ?? (string) $field->field_type,
                    'value' => $value,
                    'display_value' => $this->displayValue(
                        $value,
                        $optionOverrides[$field->field_key]
                            ?? $field->options
                            ?? []
                    ),
                    'mapping' => filled($field->contact_mapping)
                        ? (string) $field->contact_mapping
                        : null,
                    'sort_order' => (int) ($field->sort_order ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    public function displayValue(mixed $value, mixed $options): string
    {
        $optionMap = $this->normalizeOptions($options);

        if (is_array($value)) {
            return collect($value)
                ->map(fn (mixed $item): string => $optionMap[(string) $item]
                    ?? (string) $item)
                ->filter(fn (string $item): bool => $item !== '')
                ->implode(', ');
        }

        if (is_bool($value)) {
            return $value ? 'Có' : 'Không';
        }

        if ($value === null || $value === '') {
            return '—';
        }

        return $optionMap[(string) $value] ?? (string) $value;
    }

    /** @return array<string, string> */
    public function normalizeOptions(mixed $options): array
    {
        if (! is_array($options)) {
            return [];
        }

        if (! array_is_list($options)) {
            return collect($options)
                ->mapWithKeys(fn (mixed $label, mixed $value): array => [
                    (string) $value => (string) $label,
                ])
                ->all();
        }

        return collect($options)
            ->mapWithKeys(function (mixed $option): array {
                if (is_array($option)) {
                    $value = $option['value'] ?? $option['key'] ?? null;
                    $label = $option['label'] ?? $option['name'] ?? $value;

                    return $value === null
                        ? []
                        : [(string) $value => (string) $label];
                }

                return [(string) $option => (string) $option];
            })
            ->all();
    }
}
