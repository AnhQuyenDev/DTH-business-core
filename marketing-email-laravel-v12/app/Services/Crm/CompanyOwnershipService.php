<?php

namespace App\Services\Crm;

use App\Models\Crm\Company;
use App\Models\Crm\CompanyAssignment;
use App\Models\Crm\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CompanyOwnershipService
{
    public function assignOwner(
        Company $company,
        Staff $staff,
        ?string $reason,
        ?int $assignedByUserId,
    ): CompanyAssignment {
        $reason = trim((string) $reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => __('validation.company_owner_reason_required'),
            ]);
        }

        if (! $staff->canReceiveNewLeads()) {
            throw ValidationException::withMessages([
                'staff_id' => __('validation.staff_cannot_receive_leads'),
            ]);
        }

        return DB::transaction(function () use (
            $company,
            $staff,
            $reason,
            $assignedByUserId,
        ): CompanyAssignment {
            $lockedCompany = Company::query()
                ->whereKey($company->id)
                ->lockForUpdate()
                ->firstOrFail();

            $currentAssignment = CompanyAssignment::query()
                ->where('company_id', $lockedCompany->id)
                ->where('assignment_type', 'owner')
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (
                $currentAssignment?->staff_id === $staff->id
                && $lockedCompany->account_owner_staff_id === $staff->id
            ) {
                return $currentAssignment;
            }

            if ($currentAssignment !== null) {
                $currentAssignment->update([
                    'status' => 'ended',
                    'ends_at' => now(),
                ]);
            }

            $assignment = CompanyAssignment::query()->create([
                'company_id' => $lockedCompany->id,
                'staff_id' => $staff->id,
                'assignment_type' => 'owner',
                'status' => 'active',
                'reason' => $reason,
                'assigned_by_user_id' => $assignedByUserId,
                'starts_at' => now(),
            ]);

            $lockedCompany->update([
                'account_owner_staff_id' => $staff->id,
            ]);

            return $assignment;
        });
    }

    public function transferOwner(
        Company $company,
        Staff $staff,
        string $reason,
        ?int $assignedByUserId,
    ): CompanyAssignment {
        return $this->assignOwner(
            company: $company,
            staff: $staff,
            reason: $reason,
            assignedByUserId: $assignedByUserId,
        );
    }

    public function endAssignment(
        CompanyAssignment $assignment,
        string $reason,
        ?int $endedByUserId = null,
    ): void {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => __('validation.company_owner_end_reason_required'),
            ]);
        }

        DB::transaction(function () use (
            $assignment,
            $reason,
            $endedByUserId,
        ): void {
            $lockedAssignment = CompanyAssignment::query()
                ->with('company')
                ->whereKey($assignment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAssignment->status !== 'active') {
                return;
            }

            $historyReason = trim(implode("\n", array_filter([
                $lockedAssignment->reason,
                '[Kết thúc] '.$reason,
                $endedByUserId !== null
                    ? '[Người thực hiện] User #'.$endedByUserId
                    : null,
            ])));

            $lockedAssignment->update([
                'status' => 'ended',
                'ends_at' => now(),
                'reason' => $historyReason,
            ]);

            if (
                $lockedAssignment->assignment_type === 'owner'
                && $lockedAssignment->company?->account_owner_staff_id
                    === $lockedAssignment->staff_id
            ) {
                $lockedAssignment->company->update([
                    'account_owner_staff_id' => null,
                ]);
            }
        });
    }
}
