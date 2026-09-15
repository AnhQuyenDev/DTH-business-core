<?php

namespace Dth\Crm\Services;

use Dth\Crm\Models\Company;
use Dth\Crm\Models\CompanyAssignment;
use Dth\Crm\Models\CrmAgentProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CompanyOwnershipService
{
    public function assignOwner(
        Company $company,
        CrmAgentProfile $profile,
        ?int $actor = null,
        ?string $reason = null,
    ): CompanyAssignment {
        return DB::transaction(function () use ($company, $profile, $actor, $reason): CompanyAssignment {
            if (! $profile->isAvailableForNewWork()) {
                throw ValidationException::withMessages([
                    'agent_profile' => 'Nhân sự CRM hiện không sẵn sàng nhận doanh nghiệp mới.',
                ]);
            }

            CompanyAssignment::query()
                ->where('company_id', $company->id)
                ->where('assignment_type', 'owner')
                ->where('status', 'active')
                ->update(['status' => 'ended', 'ends_at' => now()]);

            $assignment = CompanyAssignment::create([
                'company_id' => $company->id,
                'agent_profile_id' => $profile->id,
                'assignment_type' => 'owner',
                'status' => 'active',
                'reason' => $reason,
                'assigned_by_user_id' => $actor,
                'starts_at' => now(),
            ]);

            $company->update(['account_owner_agent_profile_id' => $profile->id]);

            return $assignment;
        });
    }

    public function transferOwner(
        Company $company,
        CrmAgentProfile $profile,
        ?int $actor = null,
        ?string $reason = null,
    ): CompanyAssignment {
        return $this->assignOwner($company, $profile, $actor, $reason ?? 'transfer');
    }

    public function endAssignment(CompanyAssignment $assignment): void
    {
        $assignment->update(['status' => 'ended', 'ends_at' => now()]);

        if (
            $assignment->assignment_type === 'owner'
            && $assignment->company?->account_owner_agent_profile_id === $assignment->agent_profile_id
        ) {
            $assignment->company->update(['account_owner_agent_profile_id' => null]);
        }
    }
}
