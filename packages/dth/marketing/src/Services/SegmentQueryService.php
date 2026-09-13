<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Enums\LandingPageSubmissionStatus;
use Dth\Marketing\Models\ContactListMember;
use Dth\Marketing\Models\LandingPageSubmission;
use Dth\Marketing\Models\Segment;
use Illuminate\Database\Eloquent\Builder;

final class SegmentQueryService
{
    public function queryForSegment(Segment $segment): Builder
    {
        app(SegmentRuleRegistry::class)->assertRuleSetSupported((array) ($segment->rules ?? []));

        $query = LandingPageSubmission::query()
            ->where('status', LandingPageSubmissionStatus::Processed->value)
            ->whereNotNull('member_key');

        $conditions = data_get($segment->rules, 'conditions', $segment->rules ?? []);
        if (! is_array($conditions)) {
            return $query;
        }

        foreach ($conditions as $condition) {
            if (! is_array($condition)) {
                continue;
            }

            $this->applyCondition($query, $condition);
        }

        return $query;
    }

    public function countForSegment(Segment $segment): int
    {
        return $this->queryForSegment($segment)
            ->distinct()
            ->count('member_key');
    }

    /** @return array<int, array<string, mixed>> */
    public function sampleForSegment(Segment $segment, int $limit = 5): array
    {
        return $this->queryForSegment($segment)
            ->latest('submitted_at')
            ->limit(max(20, $limit * 6))
            ->get()
            ->unique('member_key')
            ->take($limit)
            ->map(static fn (LandingPageSubmission $submission): array => [
                'submission_id' => $submission->getKey(),
                'member_key' => $submission->member_key,
                'name' => $submission->display_name,
                'email' => $submission->normalized_email,
                'phone' => $submission->normalized_phone,
                'type' => $submission->submission_type,
                'source' => $submission->source,
                'lead_status' => $submission->lead_status,
                'submitted_at' => $submission->submitted_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /** @param array<string, mixed> $condition */
    private function applyCondition(Builder $query, array $condition): void
    {
        $field = trim((string) ($condition['field'] ?? ''));
        $operator = trim((string) ($condition['operator'] ?? 'equals'));
        $value = $condition['value']
            ?? $condition['value_select']
            ?? $condition['value_text']
            ?? $condition['value_number']
            ?? null;

        switch ($field) {
            case 'created_within_days':
                $days = max(1, (int) $value);
                $query->where('submitted_at', '>=', now()->subDays($days));
                break;

            case 'created_between':
                $from = $condition['value_from'] ?? null;
                $to = $condition['value_to'] ?? null;
                if (filled($from)) {
                    $query->whereDate('submitted_at', '>=', (string) $from);
                }
                if (filled($to)) {
                    $query->whereDate('submitted_at', '<=', (string) $to);
                }
                break;

            case 'has_tag':
                if (! filled($value)) {
                    break;
                }
                if ($operator === 'not_equals') {
                    $query->where(function (Builder $q) use ($value): void {
                        $q->whereNull('tags')->orWhereJsonDoesntContain('tags', (string) $value);
                    });
                } else {
                    $query->whereJsonContains('tags', (string) $value);
                }
                break;

            case 'in_list':
                if (! filled($value)) {
                    break;
                }
                $memberKeys = ContactListMember::query()
                    ->select('member_key')
                    ->where('contact_list_id', (int) $value)
                    ->where('status', 'subscribed');
                $operator === 'not_equals'
                    ? $query->whereNotIn('member_key', $memberKeys)
                    : $query->whereIn('member_key', $memberKeys);
                break;

            case 'customer_type':
                $this->stringCondition($query, 'submission_type', $operator, $value);
                break;

            case 'lead_status':
                $this->stringCondition($query, 'lead_status', $operator, $value);
                break;

            case 'service_interest':
                $this->stringCondition($query, 'service_reference', $operator, $value);
                break;

            case 'source':
                $this->stringCondition($query, 'source', $operator, $value);
                break;

            case 'utm_source':
                $this->stringCondition($query, 'utm_source', $operator, $value);
                break;

            case 'utm_campaign':
                $this->stringCondition($query, 'utm_campaign', $operator, $value);
                break;

            case 'landing_page':
                if (filled($value)) {
                    $operator === 'not_equals'
                        ? $query->where('landing_page_id', '!=', (int) $value)
                        : $query->where('landing_page_id', (int) $value);
                }
                break;
        }
    }

    private function stringCondition(Builder $query, string $column, string $operator, mixed $value): void
    {
        $value = trim((string) $value);
        if ($value === '') {
            return;
        }

        $operator === 'not_equals'
            ? $query->where(function (Builder $q) use ($column, $value): void {
                $q->whereNull($column)->orWhere($column, '!=', $value);
            })
            : $query->where($column, $value);
    }
}
