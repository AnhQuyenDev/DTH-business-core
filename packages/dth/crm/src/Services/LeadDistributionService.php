<?php

namespace Dth\Crm\Services;

use Dth\Crm\Models\CrmAgentProfile;
use Dth\Crm\Models\Lead;
use Dth\Crm\Models\LeadDistributionEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class LeadDistributionService
{
    /** @return Collection<int, CrmAgentProfile> */
    public function eligibleAgents(?array $profileIds = null): Collection
    {
        $query = CrmAgentProfile::query()
            ->assignmentEnabled()
            ->with('employee.availabilities')
            ->withCount([
                'leads as active_leads_count' => fn ($lead) => $lead->whereIn('intake_status', ['new', 'active']),
            ]);

        if ($profileIds !== null) {
            $query->whereKey($profileIds);
        }

        return $query
            ->get()
            ->filter(fn (CrmAgentProfile $profile): bool => $profile->isAvailableForNewWork())
            ->filter(fn (CrmAgentProfile $profile): bool => (int) $profile->active_leads_count < (int) $profile->lead_capacity)
            ->values();
    }

    public function distribute(Lead $lead, ?int $actor = null, ?string $strategy = null, ?array $profileIds = null): Lead
    {
        return DB::transaction(function () use ($lead, $actor, $strategy, $profileIds): Lead {
            $strategy ??= config('dth-crm.distribution.strategy', 'least_loaded');

            if ($strategy === 'manual') {
                throw ValidationException::withMessages([
                    'strategy' => 'Chiến lược thủ công yêu cầu chỉ định nhân sự CRM cụ thể.',
                ]);
            }

            $agents = $this->eligibleAgents($profileIds);

            if ($lead->company?->account_owner_agent_profile_id) {
                $target = $agents->firstWhere('id', $lead->company->account_owner_agent_profile_id);
                if (! $target) {
                    throw ValidationException::withMessages([
                        'agent_profile' => 'Nhân sự CRM phụ trách doanh nghiệp hiện không sẵn sàng nhận Lead.',
                    ]);
                }
            } else {
                $target = $this->resolve($agents, $strategy);
            }

            if (! $target) {
                throw ValidationException::withMessages([
                    'agent_profile' => 'Không có nhân sự CRM đủ điều kiện nhận Lead.',
                ]);
            }

            return $this->assign($lead, $target, $actor, $strategy);
        });
    }

    public function assign(Lead $lead, CrmAgentProfile $profile, ?int $actor = null, string $reason = 'manual'): Lead
    {
        return DB::transaction(function () use ($lead, $profile, $actor, $reason): Lead {
            if (! $this->eligibleAgents([$profile->id])->contains('id', $profile->id)) {
                throw ValidationException::withMessages([
                    'agent_profile' => 'Nhân sự CRM không đủ điều kiện nhận Lead.',
                ]);
            }

            $from = $lead->assigned_agent_profile_id;

            $lead->update([
                'assigned_agent_profile_id' => $profile->id,
                'assigned_at' => now(),
                'assigned_by_user_id' => $actor,
                'intake_status' => 'active',
            ]);

            $lead->qualification?->update([
                'assigned_agent_profile_id' => $profile->id,
                'status' => 'assigned',
            ]);

            LeadDistributionEvent::create([
                'lead_id' => $lead->id,
                'from_agent_profile_id' => $from,
                'to_agent_profile_id' => $profile->id,
                'strategy' => $reason,
                'event' => 'assigned',
                'status' => 'pending',
                'actor_user_id' => $actor,
            ]);

            return $lead->fresh();
        });
    }

    /** @return array{assigned:int,skipped:int,total:int,errors:array<int,string>} */
    public function distributeUnassigned(?string $strategy = null, ?array $profileIds = null, ?int $actor = null): array
    {
        $leads = Lead::query()
            ->whereNull('assigned_agent_profile_id')
            ->where('intake_status', 'new')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $assigned = 0;
        $errors = [];

        foreach ($leads as $lead) {
            try {
                $this->distribute($lead, $actor, $strategy, $profileIds);
                $assigned++;
            } catch (\Throwable $exception) {
                $errors[] = $lead->lead_code.': '.$exception->getMessage();
            }
        }

        return [
            'assigned' => $assigned,
            'skipped' => $leads->count() - $assigned,
            'total' => $leads->count(),
            'errors' => $errors,
        ];
    }

    private function resolve(Collection $agents, string $strategy): ?CrmAgentProfile
    {
        if ($agents->isEmpty()) {
            return null;
        }

        if ($strategy === 'round_robin') {
            $last = LeadDistributionEvent::query()
                ->where('event', 'assigned')
                ->whereNotNull('to_agent_profile_id')
                ->latest('id')
                ->value('to_agent_profile_id');

            $ids = $agents->sortBy('id')->pluck('id')->values();
            $index = $last ? $ids->search($last) : false;
            $next = $index === false ? 0 : (($index + 1) % $ids->count());

            return $agents->firstWhere('id', $ids[$next]);
        }

        if ($strategy === 'weighted') {
            return $agents
                ->sortBy(fn (CrmAgentProfile $profile): array => [
                    (float) $profile->active_leads_count / max((float) $profile->distribution_weight, 0.01),
                    $profile->id,
                ])
                ->first();
        }

        return $agents
            ->sortBy(fn (CrmAgentProfile $profile): array => [(int) $profile->active_leads_count, $profile->id])
            ->first();
    }

    public function accept(Lead $lead, int $profileId): Lead
    {
        if ($lead->assigned_agent_profile_id !== $profileId) {
            throw ValidationException::withMessages([
                'lead' => 'Lead không được phân cho nhân sự CRM này.',
            ]);
        }

        LeadDistributionEvent::query()
            ->where('lead_id', $lead->id)
            ->where('to_agent_profile_id', $profileId)
            ->where('status', 'pending')
            ->latest()
            ->first()?->update([
                'status' => 'accepted',
                'event' => 'accepted',
                'responded_at' => now(),
            ]);

        return $lead;
    }

    public function reject(Lead $lead, int $profileId, string $reason): Lead
    {
        if ($lead->assigned_agent_profile_id !== $profileId) {
            throw ValidationException::withMessages([
                'lead' => 'Lead không được phân cho nhân sự CRM này.',
            ]);
        }

        LeadDistributionEvent::query()
            ->where('lead_id', $lead->id)
            ->where('to_agent_profile_id', $profileId)
            ->where('status', 'pending')
            ->latest()
            ->first()?->update([
                'status' => 'rejected',
                'event' => 'rejected',
                'reason' => $reason,
                'responded_at' => now(),
            ]);

        $lead->update([
            'assigned_agent_profile_id' => null,
            'assigned_at' => null,
            'intake_status' => 'new',
        ]);
        $lead->qualification?->update([
            'assigned_agent_profile_id' => null,
            'status' => 'new',
        ]);

        return $lead;
    }
}
