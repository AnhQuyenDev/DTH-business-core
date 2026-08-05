<?php

namespace App\Services\Crm;

use App\Models\Crm\Customer;
use App\Models\Marketing\Segment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class SegmentQueryService
{
    public function queryForCustomerSegment(Segment $segment): Builder
    {
        $rules = $segment->rules ?? [];

        return $this->applyCustomerRules(Customer::query()->with(['tags', 'lists']), $rules);
    }

    public function countForCustomerSegment(Segment $segment): int
    {
        return $this->queryForCustomerSegment($segment)->count();
    }

    public function sampleForCustomerSegment(Segment $segment, int $limit = 5): array
    {
        return $this->queryForCustomerSegment($segment)
            ->limit($limit)
            ->get()
            ->map(static function (Customer $customer): array {
                return [
                    'id' => $customer->id,
                    'customer_code' => $customer->customer_code,
                    'display_name' => $customer->display_name,
                    'email' => $customer->email,
                    'status' => $customer->status instanceof \BackedEnum ? $customer->status->value : $customer->status,
                    'consent_status' => $customer->consent_status instanceof \BackedEnum ? $customer->consent_status->value : $customer->consent_status,
                ];
            })
            ->all();
    }

    public function applyCustomerRules(Builder $query, array $rules): Builder
    {
        $conditions = $rules['conditions'] ?? $rules;

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? null;
            $value = $condition['value'] ?? null;

            if (! $field || ! $operator) {
                continue;
            }

            match ($field) {
                'customer_status_equals' => $query->where('status', $value),
                'customer_lifecycle_equals' => $query->where('lifecycle_stage', $value),
                'customer_owner_equals' => $query->whereHas('assignments', fn (Builder $q) => $q
                    ->where('staff_id', $value)
                    ->where('assignment_type', 'owner')
                    ->where('status', 'active')
                ),
                'customer_type_equals' => $query->where('customer_type', $value),
                'has_customer_tag' => $query->whereHas('tags', fn (Builder $q) => $q->where('tags.id', $value)),
                'in_customer_list' => $query->whereHas('lists', fn (Builder $q) => $q->where('customer_lists.id', $value)),
                'consent_status_equals' => $query->where('consent_status', $value),
                'converted_within_days' => $query->where('converted_at', '>=', now()->subDays((int) $value)),
                'converted_between_dates' => $this->applyDateRange($query, 'converted_at', $condition),
                'business_tax_verified' => $query->whereHas('contact.businessProfile', fn (Builder $q) => $q->where('tax_verification_status', 'verified')),
                default => null,
            };
        }

        return $query;
    }

    protected function applyDateRange(Builder $query, string $column, array $condition): Builder
    {
        $from = $condition['value_from'] ?? (is_array($condition['value'] ?? null) ? ($condition['value']['from'] ?? null) : null);
        $to = $condition['value_to'] ?? (is_array($condition['value'] ?? null) ? ($condition['value']['to'] ?? null) : null);

        return $query
            ->when($from, fn (Builder $query) => $query->whereDate($column, '>=', Carbon::parse($from)->toDateString()))
            ->when($to, fn (Builder $query) => $query->whereDate($column, '<=', Carbon::parse($to)->toDateString()));
    }
}
