<?php

namespace Dth\Crm\Services;

use Dth\Crm\Models\Company;
use Dth\Crm\Models\CompanyMatchCandidate;
use Dth\Crm\Models\Contact;
use Dth\Crm\Models\ContactQualification;
use Dth\Crm\Models\CrmAgentProfile;
use Dth\Crm\Models\Customer;
use Dth\Crm\Models\Lead;
use Illuminate\Database\Eloquent\Builder;

final class CrmAnalyticsService
{
    /** @return array<string, mixed> */
    public function snapshot(): array
    {
        $contacts = Contact::query()->count();
        $companies = Company::query()->count();
        $leads = Lead::query()->count();
        $qualified = ContactQualification::query()->where('status', 'qualified')->count();
        $customers = Customer::query()->count();
        $customersFromLeads = Customer::query()->whereNotNull('origin_lead_id')->count();

        return [
            'metrics' => [
                'contacts' => $contacts,
                'personal_contacts' => Contact::query()->where('type', 'personal')->count(),
                'business_contacts' => Contact::query()->where('type', 'business')->count(),
                'companies' => $companies,
                'leads' => $leads,
                'new_leads' => Lead::query()->where('intake_status', 'new')->count(),
                'active_leads' => Lead::query()->where('intake_status', 'active')->count(),
                'unassigned_leads' => Lead::query()
                    ->whereNull('assigned_agent_profile_id')
                    ->whereNotIn('intake_status', ['duplicate', 'spam', 'closed', 'converted_to_opportunity'])
                    ->count(),
                'qualified' => $qualified,
                'follow_ups_due' => ContactQualification::query()
                    ->whereIn('status', ['contacting', 'follow_up'])
                    ->whereNotNull('next_follow_up_at')
                    ->where('next_follow_up_at', '<=', now())
                    ->count(),
                'customers' => $customers,
                'active_customers' => Customer::query()->where('status', 'active')->count(),
                'total_revenue' => (float) Customer::query()->sum('total_revenue'),
                'active_agents' => CrmAgentProfile::query()->assignmentEnabled()->count(),
                'pending_matches' => CompanyMatchCandidate::query()->where('status', 'pending')->count(),
                'lead_to_customer_rate' => $leads > 0 ? round(($customersFromLeads / $leads) * 100, 1) : 0.0,
            ],
            'pipeline' => [
                ['key' => 'contacts', 'value' => $contacts],
                ['key' => 'leads', 'value' => $leads],
                ['key' => 'qualified', 'value' => $qualified],
                ['key' => 'customers', 'value' => $customers],
            ],
            'lead_statuses' => $this->countsBy(Lead::query(), 'intake_status'),
            'qualification_statuses' => $this->countsBy(ContactQualification::query(), 'status'),
            'customer_statuses' => $this->countsBy(Customer::query(), 'status'),
            'recent_leads' => Lead::query()
                ->with([
                    'contact:id,display_name',
                    'company:id,legal_name',
                    'assignedAgentProfile.employee:id,employee_code,full_name',
                ])
                ->latest('id')
                ->limit(6)
                ->get()
                ->map(fn (Lead $lead): array => [
                    'id' => $lead->id,
                    'code' => $lead->lead_code,
                    'name' => $lead->contact?->display_name
                        ?? $lead->company?->legal_name
                        ?? $lead->title
                        ?? '—',
                    'service' => $lead->service_interest ?: '—',
                    'status' => $lead->intake_status,
                    'owner' => $lead->assignedAgentProfile?->employee?->full_name,
                    'created_at' => $lead->created_at,
                ])
                ->all(),
            'agent_workload' => CrmAgentProfile::query()
                ->assignmentEnabled()
                ->with('employee:id,employee_code,full_name')
                ->withCount([
                    'leads as open_leads_count' => fn (Builder $query): Builder => $query
                        ->whereNotIn('intake_status', ['duplicate', 'spam', 'closed', 'converted_to_opportunity']),
                ])
                ->orderByDesc('open_leads_count')
                ->orderBy('id')
                ->limit(6)
                ->get()
                ->map(fn (CrmAgentProfile $profile): array => [
                    'id' => $profile->id,
                    'name' => $profile->employee?->full_name ?? $profile->displayLabel(),
                    'open_leads' => (int) $profile->open_leads_count,
                    'capacity' => max(1, (int) $profile->lead_capacity),
                    'load_percent' => min(
                        100,
                        round(((int) $profile->open_leads_count / max(1, (int) $profile->lead_capacity)) * 100),
                    ),
                ])
                ->all(),
        ];
    }

    /** @return array<string, int> */
    private function countsBy(Builder $query, string $column): array
    {
        return $query
            ->selectRaw($column.', COUNT(*) as aggregate')
            ->groupBy($column)
            ->pluck('aggregate', $column)
            ->map(fn ($value): int => (int) $value)
            ->all();
    }
}
