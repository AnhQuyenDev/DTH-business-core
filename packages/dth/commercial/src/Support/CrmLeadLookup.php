<?php

namespace Dth\Commercial\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * Optional CRM bridge used only by the Commercial admin UI.
 *
 * The class deliberately avoids a compile-time dependency on dth/crm so the
 * Commercial package remains detachable. When CRM is not installed (or its
 * tables have not been migrated), every method degrades to an empty result.
 */
final class CrmLeadLookup
{
    private const LEAD_MODEL = 'Dth\\Crm\\Models\\Lead';

    public function available(): bool
    {
        return class_exists(self::LEAD_MODEL) && Schema::hasTable('crm_leads');
    }

    /** @return array<string, string> */
    public function search(?string $search, int $limit = 30): array
    {
        if (! $this->available()) {
            return [];
        }

        $needle = trim((string) $search);
        $query = $this->query()
            ->whereNotIn('intake_status', ['duplicate', 'spam', 'closed'])
            ->latest('id');

        if ($needle !== '') {
            $query->where(function (Builder $query) use ($needle): void {
                $like = '%'.$needle.'%';

                $query
                    ->where('lead_code', 'like', $like)
                    ->orWhere('title', 'like', $like)
                    ->orWhere('service_interest', 'like', $like)
                    ->orWhereHas('contact', fn (Builder $contact): Builder => $contact->where('display_name', 'like', $like))
                    ->orWhereHas('company', fn (Builder $company): Builder => $company->where('legal_name', 'like', $like));
            });
        }

        return $query
            ->limit(max(1, min($limit, 50)))
            ->get()
            ->mapWithKeys(fn (object $lead): array => [(string) $lead->getKey() => $this->labelFor($lead)])
            ->all();
    }

    public function label(mixed $reference): ?string
    {
        if (blank($reference)) {
            return null;
        }

        if (! $this->available() || ! ctype_digit((string) $reference)) {
            return (string) $reference;
        }

        $lead = $this->query()->find((int) $reference);

        return $lead ? $this->labelFor($lead) : (string) $reference;
    }

    /**
     * @return array{
     *     lead_reference:string,
     *     lead_code:?string,
     *     title:?string,
     *     contact_reference:?string,
     *     contact_name:?string,
     *     company_reference:?string,
     *     company_name:?string,
     *     owner_reference:?string,
     *     owner_name:?string,
     *     service_reference:?string,
     *     service_name:?string,
     *     estimated_value:mixed
     * }|null
     */
    public function snapshot(mixed $reference): ?array
    {
        if (! $this->available() || blank($reference) || ! ctype_digit((string) $reference)) {
            return null;
        }

        $lead = $this->query()->find((int) $reference);
        if (! $lead) {
            return null;
        }

        return [
            'lead_reference' => (string) $lead->getKey(),
            'lead_code' => filled($lead->lead_code) ? (string) $lead->lead_code : null,
            'title' => filled($lead->title) ? (string) $lead->title : null,
            'contact_reference' => filled($lead->contact_id) ? (string) $lead->contact_id : null,
            'contact_name' => filled($lead->contact?->display_name) ? (string) $lead->contact->display_name : null,
            'company_reference' => filled($lead->company_id) ? (string) $lead->company_id : null,
            'company_name' => filled($lead->company?->legal_name) ? (string) $lead->company->legal_name : null,
            'owner_reference' => filled($lead->assignedAgentProfile?->employee_id)
                ? (string) $lead->assignedAgentProfile->employee_id
                : null,
            'owner_name' => filled($lead->assignedAgentProfile?->employee?->full_name)
                ? (string) $lead->assignedAgentProfile->employee->full_name
                : null,
            'service_reference' => filled($lead->service_reference) ? (string) $lead->service_reference : null,
            'service_name' => filled($lead->service_interest) ? (string) $lead->service_interest : null,
            'estimated_value' => $lead->estimated_value,
        ];
    }

    private function query(): Builder
    {
        $model = self::LEAD_MODEL;

        return $model::query()->with([
            'contact',
            'company',
            'assignedAgentProfile.employee',
        ]);
    }

    private function labelFor(object $lead): string
    {
        $identity = trim(implode(' · ', array_filter([
            $lead->lead_code,
            $lead->company?->legal_name ?: $lead->contact?->display_name,
        ])));

        $context = trim(implode(' — ', array_filter([
            $lead->title,
            $lead->service_interest,
        ])));

        return trim($identity.($context !== '' ? ' — '.$context : '')) ?: 'Lead #'.$lead->getKey();
    }
}
