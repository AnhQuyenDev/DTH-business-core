<?php

namespace App\Services\Crm;

use App\Models\Crm\Company;
use App\Models\Crm\CompanyAssignment;
use App\Models\Crm\Staff;
use Illuminate\Support\Facades\DB;

final class CompanyOwnershipService
{
    public function assignOwner(
        Company $company,
        Staff $staff,
        ?string $reason,
        ?int $assignedByUserId,
    ): CompanyAssignment {
        return DB::transaction(function () use (
            $company,
            $staff,
            $reason,
            $assignedByUserId,
        ): CompanyAssignment {
            Company::query()
                ->whereKey($company->id)
                ->lockForUpdate()
                ->firstOrFail();

            CompanyAssignment::query()
                ->where('company_id', $company->id)
                ->where('assignment_type', 'owner')
                ->where('status', 'active')
                ->update([
                    'status' => 'ended',
                    'ends_at' => now(),
                ]);

            $assignment = CompanyAssignment::query()->create([
                'company_id' => $company->id,
                'staff_id' => $staff->id,
                'assignment_type' => 'owner',
                'status' => 'active',
                'reason' => $reason,
                'assigned_by_user_id' => $assignedByUserId,
                'starts_at' => now(),
            ]);

            $company->update([
                'account_owner_staff_id' => $staff->id,
            ]);

            return $assignment;
        });
    }

    public function endAssignment(
        CompanyAssignment $assignment
    ): void {
        DB::transaction(function () use ($assignment): void {
            $assignment = CompanyAssignment::query()
                ->whereKey($assignment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $assignment->update([
                'status' => 'ended',
                'ends_at' => now(),
            ]);

            if (
                $assignment->assignment_type === 'owner'
                && $assignment->company?->account_owner_staff_id
                    === $assignment->staff_id
            ) {
                $assignment->company->update([
                    'account_owner_staff_id' => null,
                ]);
            }
        });
    }
}
