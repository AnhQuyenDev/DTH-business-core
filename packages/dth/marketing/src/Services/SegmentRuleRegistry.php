<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Contracts\LeadProvider;
use Dth\Marketing\Models\ContactList;
use Dth\Marketing\Models\LandingPage;
use Dth\Marketing\Support\UiText;
use Illuminate\Validation\ValidationException;

final class SegmentRuleRegistry
{
    public function __construct(
        private readonly LeadProvider $leadProvider,
    ) {}

    /**
     * @return array<string, array{label:string,operators:array<int,string>,value_type:string,options?:array<string|int,string>}>
     */
    public function definitions(): array
    {
        $definitions = [
            'created_within_days' => [
                'label' => UiText::get('segment.rule.created_within_days', 'Created within days'),
                'operators' => ['within'],
                'value_type' => 'number',
            ],
            'created_between' => [
                'label' => UiText::get('segment.rule.created_between', 'Created between'),
                'operators' => ['between'],
                'value_type' => 'date_range',
            ],
            'has_tag' => [
                'label' => UiText::get('segment.rule.has_tag', 'Has tag'),
                'operators' => ['equals', 'not_equals'],
                'value_type' => 'text',
            ],
            'in_list' => [
                'label' => UiText::get('segment.rule.in_list', 'In marketing list'),
                'operators' => ['equals', 'not_equals'],
                'value_type' => 'select',
                'options' => ContactList::query()
                    ->where('status', 'active')
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all(),
            ],
            'customer_type' => [
                'label' => UiText::get('segment.rule.customer_type', 'Customer type'),
                'operators' => ['equals', 'not_equals'],
                'value_type' => 'select',
                'options' => [
                    'personal' => UiText::get('form.audience.personal', 'Personal'),
                    'business' => UiText::get('form.audience.business', 'Business'),
                ],
            ],
            'service_interest' => [
                'label' => UiText::get('segment.rule.service_interest', 'Service interest'),
                'operators' => ['equals', 'not_equals'],
                'value_type' => 'text',
            ],
            'source' => [
                'label' => UiText::get('segment.rule.source', 'Acquisition source'),
                'operators' => ['equals', 'not_equals'],
                'value_type' => 'text',
            ],
            'utm_source' => [
                'label' => UiText::get('segment.rule.utm_source', 'UTM source'),
                'operators' => ['equals', 'not_equals'],
                'value_type' => 'text',
            ],
            'utm_campaign' => [
                'label' => UiText::get('segment.rule.utm_campaign', 'UTM campaign'),
                'operators' => ['equals', 'not_equals'],
                'value_type' => 'text',
            ],
            'landing_page' => [
                'label' => UiText::get('segment.rule.landing_page', 'Landing Page'),
                'operators' => ['equals', 'not_equals'],
                'value_type' => 'select',
                'options' => LandingPage::query()->orderBy('name')->pluck('name', 'id')->all(),
            ],
        ];

        // Lead status is not offered when CRM cannot authoritatively return it.
        // This avoids the legacy failure mode where a rule could be configured
        // and silently ignored by the backend.
        if ($this->leadProvider->available()) {
            $definitions['lead_status'] = [
                'label' => UiText::get('segment.rule.lead_status', 'Lead status'),
                'operators' => ['equals', 'not_equals'],
                'value_type' => 'text',
            ];
        }

        return $definitions;
    }

    /** @return array<string, string> */
    public function fieldOptions(): array
    {
        return collect($this->definitions())
            ->mapWithKeys(static fn (array $definition, string $key): array => [$key => $definition['label']])
            ->all();
    }

    /** @return array<string, string> */
    public function operatorOptions(?string $field): array
    {
        $operators = $this->definitions()[$field]['operators'] ?? ['equals'];
        $labels = [
            'equals' => UiText::get('segment.operator.equals', 'Equals'),
            'not_equals' => UiText::get('segment.operator.not_equals', 'Does not equal'),
            'within' => UiText::get('segment.operator.within', 'Within'),
            'between' => UiText::get('segment.operator.between', 'Between'),
        ];

        return collect($operators)
            ->mapWithKeys(static fn (string $operator): array => [$operator => $labels[$operator] ?? $operator])
            ->all();
    }

    /** @return array<string|int, string> */
    public function valueOptions(?string $field): array
    {
        return (array) ($this->definitions()[$field]['options'] ?? []);
    }

    public function valueType(?string $field): string
    {
        return (string) ($this->definitions()[$field]['value_type'] ?? 'text');
    }

    /** @param array<string, mixed> $ruleSet */
    public function assertRuleSetSupported(array $ruleSet): void
    {
        $conditions = $ruleSet['conditions'] ?? $ruleSet;
        if (! is_array($conditions)) {
            throw ValidationException::withMessages([
                'rules' => UiText::get('segment.validation.rule_list', 'Segment rules must be a list of conditions.'),
            ]);
        }

        $definitions = $this->definitions();

        foreach ($conditions as $index => $condition) {
            if (! is_array($condition)) {
                continue;
            }

            $field = trim((string) ($condition['field'] ?? ''));
            if ($field === '') {
                continue;
            }

            if (! isset($definitions[$field])) {
                throw ValidationException::withMessages([
                    "rules.conditions.{$index}.field" => UiText::get('segment.validation.unsupported_rule', "Segment rule ':field' is not supported by the currently installed providers.", ['field' => $field]),
                ]);
            }

            $operator = trim((string) ($condition['operator'] ?? ''));
            if (! in_array($operator, $definitions[$field]['operators'], true)) {
                throw ValidationException::withMessages([
                    "rules.conditions.{$index}.operator" => UiText::get('segment.validation.unsupported_operator', "Operator ':operator' is not supported for ':field'.", ['operator' => $operator, 'field' => $field]),
                ]);
            }
        }
    }
}
