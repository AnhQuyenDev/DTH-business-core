<?php

namespace App\Services\Marketing;

use App\Models\Crm\PersonalContactProfile;
use App\Models\Marketing\Contact;
use App\Models\Marketing\Segment;
use Illuminate\Database\Eloquent\Builder;

class SegmentQueryService
{
    public function queryForSegment(Segment $segment): Builder
    {
        $rules = $segment->rules ?? [];

        return $this->applyRules(Contact::query(), $rules);
    }

    public function countForSegment(Segment $segment): int
    {
        return $this->queryForSegment($segment)->count();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function sampleForSegment(Segment $segment, int $limit = 5): array
    {
        return $this->queryForSegment($segment)
            ->limit($limit)
            ->get()
            ->map(static function (Contact $contact): array {
                return [
                    'id' => $contact->id,
                    'full_name' => $contact->full_name,
                    'email' => $contact->email,
                ];
            })
            ->all();
    }

    protected function applyRules(Builder $query, array $rules): Builder
    {
        $conditions = $rules['conditions'] ?? $rules;

        $contactIds = null;

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? null;
            $value = $condition['value'] ?? null;

            if (! $field || ! $operator) {
                continue;
            }

            if (in_array($field, ['created_within_days', 'created_between_dates'], true)) {
                $ids = $this->applyProfileCondition($field, $operator, $value, $condition);
                if ($ids !== null) {
                    $contactIds = $contactIds === null ? $ids : array_intersect($contactIds, $ids);
                }

                continue;
            }

            match ($field) {
                'has_tag' => null, // Tags pivot removed
                'in_list' => null, // Lists pivot removed
                'status_equals' => null, // Dropped column
                'consent_status_equals' => null, // Dropped column
                default => null,
            };
        }

        if ($contactIds !== null) {
            $query->whereIn('id', $contactIds);
        }

        return $query;
    }

    private function applyProfileCondition(string $field, string $operator, mixed $value, array $condition): ?array
    {
        $profileQuery = PersonalContactProfile::query();

        match ($field) {
            'created_within_days' => $profileQuery->where('created_at', '>=', now()->subDays((int) $value)),
            'created_between_dates' => $this->applyCreatedBetweenDates($profileQuery, $condition),
            default => null,
        };

        return $profileQuery->pluck('contact_id')->toArray();
    }

    protected function applyCreatedBetweenDates(Builder $query, array $condition): Builder
    {
        return $query;
    }
}
