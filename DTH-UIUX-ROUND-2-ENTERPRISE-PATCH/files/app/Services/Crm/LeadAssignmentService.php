<?php

namespace App\Services\Crm;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\LeadIntakeStatus;
use App\Events\Crm\LeadAssigned;
use App\Models\Crm\Company;
use App\Models\Crm\Lead;
use App\Models\Crm\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class LeadAssignmentService
{
    public function __construct(
        private readonly CompanyOwnershipService $companyOwnershipService,
        private readonly ContactQualificationWorkflowService $workflowService,
    ) {}

    public function assign(
        Lead $lead,
        Staff $staff,
        ?int $assignedByUserId,
        ?string $reason = null,
        bool $force = false,
        bool $transferCompanyOwner = false,
    ): Lead {
        $reason = trim((string) $reason);

        if ($force && $reason === '') {
            throw ValidationException::withMessages([
                'reason' => __('validation.lead_reassignment_reason_required'),
            ]);
        }

        return DB::transaction(function () use (
            $lead,
            $staff,
            $assignedByUserId,
            $reason,
            $force,
            $transferCompanyOwner,
        ): Lead {
            $lockedLead = Lead::query()
                ->with(['qualification', 'company.accountOwner'])
                ->whereKey($lead->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedStaff = Staff::query()
                ->with('department')
                ->whereKey($staff->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertLeadCanBeAssigned($lockedLead);
            $this->assertStaffCanReceiveLead($lockedStaff);

            if ($lockedLead->qualification === null) {
                throw ValidationException::withMessages([
                    'lead' => __('validation.lead_qualification_missing'),
                ]);
            }

            $fromStaffId = $lockedLead->assigned_staff_id;

            if ($fromStaffId === $lockedStaff->id) {
                return $lockedLead->fresh([
                    'assignedStaff',
                    'qualification',
                    'company.accountOwner',
                ]);
            }

            if ($fromStaffId !== null && ! $force) {
                throw ValidationException::withMessages([
                    'assigned_staff_id' => __('validation.lead_already_assigned'),
                ]);
            }

            $company = $this->lockCompany($lockedLead);
            $accountOwnerId = $company?->account_owner_staff_id;
            $companyOwnerTransferred = false;

            if (
                $accountOwnerId !== null
                && $accountOwnerId !== $lockedStaff->id
                && ! $force
            ) {
                throw ValidationException::withMessages([
                    'assigned_staff_id' => __('validation.company_has_different_owner'),
                ]);
            }

            $effectiveReason = $reason !== ''
                ? $reason
                : 'Phân công Lead lần đầu';

            if ($company !== null && $accountOwnerId === null) {
                $this->companyOwnershipService->assignOwner(
                    company: $company,
                    staff: $lockedStaff,
                    reason: 'Tự động gán từ Lead đầu tiên: '.$effectiveReason,
                    assignedByUserId: $assignedByUserId,
                );

                $companyOwnerTransferred = true;
            } elseif (
                $company !== null
                && $accountOwnerId !== $lockedStaff->id
                && $force
                && $transferCompanyOwner
            ) {
                $this->companyOwnershipService->transferOwner(
                    company: $company,
                    staff: $lockedStaff,
                    reason: $effectiveReason,
                    assignedByUserId: $assignedByUserId,
                );

                $companyOwnerTransferred = true;
            }

            $lockedLead->update([
                'assigned_staff_id' => $lockedStaff->id,
                'assigned_at' => now(),
                'assigned_by_user_id' => $assignedByUserId,
                'intake_status' => LeadIntakeStatus::Active->value,
            ]);

            $this->workflowService->syncAssignedStaff(
                qualification: $lockedLead->qualification,
                staffId: $lockedStaff->id,
                actorUserId: $assignedByUserId,
            );

            DB::afterCommit(function () use (
                $lockedLead,
                $fromStaffId,
                $lockedStaff,
                $assignedByUserId,
                $effectiveReason,
                $force,
                $companyOwnerTransferred,
            ): void {
                LeadAssigned::dispatch(
                    leadId: $lockedLead->id,
                    fromStaffId: $fromStaffId,
                    toStaffId: $lockedStaff->id,
                    assignedByUserId: $assignedByUserId,
                    reason: $effectiveReason,
                    forced: $force,
                    companyOwnerTransferred: $companyOwnerTransferred,
                );
            });

            return $lockedLead->fresh([
                'assignedStaff',
                'assignedBy',
                'qualification',
                'company.accountOwner',
            ]);
        });
    }

    private function assertLeadCanBeAssigned(Lead $lead): void
    {
        $status = $lead->intake_status?->value ?? $lead->intake_status;

        if (in_array($status, [
            LeadIntakeStatus::Duplicate->value,
            LeadIntakeStatus::Spam->value,
            LeadIntakeStatus::Closed->value,
            LeadIntakeStatus::ConvertedToOpportunity->value,
        ], true)) {
            throw ValidationException::withMessages([
                'lead' => __('validation.lead_terminal_not_assignable'),
            ]);
        }

        $qualificationStatus = $lead->qualification?->status;

        if (
            $qualificationStatus instanceof ContactQualificationStatus
            && $qualificationStatus->isTerminal()
        ) {
            throw ValidationException::withMessages([
                'lead' => __('validation.lead_terminal_not_assignable'),
            ]);
        }
    }

    private function assertStaffCanReceiveLead(Staff $staff): void
    {
        $staff->loadMissing(['department', 'user']);

        if (
            ! $staff->canReceiveNewLeads()
            || $staff->department?->function_key !== 'customer_service'
            || ! $staff->user?->is_active
            || ! $staff->user?->isCustomerServiceStaff()
        ) {
            throw ValidationException::withMessages([
                'staff_id' => __('validation.staff_cannot_receive_leads'),
            ]);
        }
    }

    private function lockCompany(Lead $lead): ?Company
    {
        if ($lead->company_id === null) {
            return null;
        }

        return Company::query()
            ->with('accountOwner')
            ->whereKey($lead->company_id)
            ->lockForUpdate()
            ->first();
    }
}
