<?php

namespace App\Services\Crm;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\LeadIntakeStatus;
use App\Enums\Crm\QualificationResult;
use App\Events\Crm\ContactQualificationTransitioned;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\Lead;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ContactQualificationWorkflowService
{
    private const ALLOWED_TRANSITIONS = [
        'new' => [
            'assigned',
            'duplicate',
            'spam',
            'archived',
        ],
        'assigned' => [
            'contacting',
            'unqualified',
            'duplicate',
            'spam',
            'archived',
        ],
        'contacting' => [
            'follow_up',
            'qualified',
            'unqualified',
            'duplicate',
            'spam',
        ],
        'follow_up' => [
            'contacting',
            'qualified',
            'unqualified',
            'duplicate',
            'spam',
            'archived',
        ],
        'qualified' => [
            'unqualified',
        ],
        'unqualified' => [
            'archived',
        ],
        'duplicate' => [
            'archived',
        ],
        'spam' => [
            'archived',
        ],
        'converted' => [],
        'archived' => [],
    ];

    public function transition(
        ContactQualification $qualification,
        ContactQualificationStatus $to,
        array $data = [],
        ?int $actorUserId = null,
    ): ContactQualification {
        return DB::transaction(function () use (
            $qualification,
            $to,
            $data,
            $actorUserId,
        ): ContactQualification {
            $locked = ContactQualification::query()
                ->with('lead')
                ->whereKey($qualification->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->lead === null) {
                throw ValidationException::withMessages([
                    'lead' => __('validation.lead_qualification_missing'),
                ]);
            }

            $from = $locked->status?->value
                ?? (string) $locked->status;

            if ($from === $to->value) {
                return $locked;
            }

            if (! in_array(
                $to->value,
                self::ALLOWED_TRANSITIONS[$from] ?? [],
                true,
            )) {
                throw ValidationException::withMessages([
                    'status' => __('validation.invalid_qualification_transition', [
                        'from' => ContactQualificationStatus::tryFrom($from)?->label()
                            ?? $from,
                        'to' => $to->label(),
                    ]),
                ]);
            }

            $this->validateTransitionData($locked, $to, $data);

            $payload = $this->buildQualificationPayload(
                qualification: $locked,
                to: $to,
                data: $data,
            );

            $locked->update($payload);

            $this->syncLeadState(
                lead: $locked->lead,
                to: $to,
                data: $data,
            );

            $changedValues = Arr::only($payload, [
                'qualification_result',
                'priority',
                'score',
                'service_interest',
                'estimated_value',
                'next_follow_up_at',
                'unqualified_reason',
                'qualified_by_staff_id',
            ]);

            DB::afterCommit(function () use (
                $locked,
                $from,
                $to,
                $actorUserId,
                $changedValues,
            ): void {
                ContactQualificationTransitioned::dispatch(
                    qualificationId: $locked->id,
                    leadId: $locked->lead_id,
                    fromStatus: $from,
                    toStatus: $to->value,
                    actorUserId: $actorUserId,
                    changedValues: $changedValues,
                );
            });

            return $locked->fresh([
                'lead',
                'assignedStaff',
                'qualifiedBy',
            ]);
        });
    }

    public function syncAssignedStaff(
        ContactQualification $qualification,
        int $staffId,
        ?int $actorUserId = null,
    ): ContactQualification {
        $currentStatus = $qualification->status?->value
            ?? (string) $qualification->status;

        if ($currentStatus === ContactQualificationStatus::New->value) {
            return $this->transition(
                qualification: $qualification,
                to: ContactQualificationStatus::Assigned,
                data: [
                    'assigned_staff_id' => $staffId,
                ],
                actorUserId: $actorUserId,
            );
        }

        $qualification->update([
            'assigned_staff_id' => $staffId,
        ]);

        return $qualification->fresh();
    }

    private function validateTransitionData(
        ContactQualification $qualification,
        ContactQualificationStatus $to,
        array $data,
    ): void {
        if (
            $to === ContactQualificationStatus::FollowUp
            && blank($data['next_follow_up_at'] ?? null)
        ) {
            throw ValidationException::withMessages([
                'next_follow_up_at' => __('validation.next_follow_up_required'),
            ]);
        }

        if ($to === ContactQualificationStatus::Qualified) {
            if (blank($data['service_interest'] ?? $qualification->service_interest)) {
                throw ValidationException::withMessages([
                    'service_interest' => __('validation.service_interest_required'),
                ]);
            }
        }

        if ($to === ContactQualificationStatus::Unqualified) {
            if (blank($data['unqualified_reason'] ?? null)) {
                throw ValidationException::withMessages([
                    'unqualified_reason' => __('validation.unqualified_reason_required'),
                ]);
            }

            $result = $data['qualification_result'] ?? null;

            if (! in_array($result, [
                QualificationResult::NoNeed->value,
                QualificationResult::Unreachable->value,
                QualificationResult::InvalidInformation->value,
            ], true)) {
                throw ValidationException::withMessages([
                    'qualification_result' => __('validation.unqualified_result_required'),
                ]);
            }
        }

        if (
            in_array($to, [
                ContactQualificationStatus::Duplicate,
                ContactQualificationStatus::Spam,
            ], true)
            && blank($data['reason'] ?? null)
        ) {
            throw ValidationException::withMessages([
                'reason' => __('validation.reason_required'),
            ]);
        }
    }

    private function buildQualificationPayload(
        ContactQualification $qualification,
        ContactQualificationStatus $to,
        array $data,
    ): array {
        $payload = Arr::only($data, [
            'assigned_staff_id',
            'priority',
            'score',
            'qualification_result',
            'service_interest',
            'estimated_value',
            'next_follow_up_at',
            'qualified_by_staff_id',
            'unqualified_reason',
        ]);

        $payload['status'] = $to->value;

        if ($to === ContactQualificationStatus::Contacting) {
            $contactedAt = $data['contacted_at'] ?? now();
            $payload['first_contacted_at'] =
                $qualification->first_contacted_at ?? $contactedAt;
            $payload['last_contacted_at'] = $contactedAt;
        }

        if ($to === ContactQualificationStatus::FollowUp) {
            $payload['last_contacted_at'] =
                $qualification->last_contacted_at ?? now();
        }

        if ($to === ContactQualificationStatus::Qualified) {
            $payload['qualification_result'] =
                QualificationResult::ConfirmedNeed->value;
            $payload['qualified_at'] = now();
            $payload['next_follow_up_at'] = null;
        }

        if ($to === ContactQualificationStatus::Unqualified) {
            $payload['next_follow_up_at'] = null;
        }

        if (in_array($to, [
            ContactQualificationStatus::Duplicate,
            ContactQualificationStatus::Spam,
            ContactQualificationStatus::Archived,
        ], true)) {
            $payload['next_follow_up_at'] = null;
        }

        return $payload;
    }

    private function syncLeadState(
        Lead $lead,
        ContactQualificationStatus $to,
        array $data,
    ): void {
        $payload = [];

        if (in_array($to, [
            ContactQualificationStatus::Assigned,
            ContactQualificationStatus::Contacting,
            ContactQualificationStatus::FollowUp,
            ContactQualificationStatus::Qualified,
        ], true)) {
            $payload['intake_status'] = LeadIntakeStatus::Active->value;
        }

        if ($to === ContactQualificationStatus::Duplicate) {
            $payload['intake_status'] = LeadIntakeStatus::Duplicate->value;
        }

        if ($to === ContactQualificationStatus::Spam) {
            $payload['intake_status'] = LeadIntakeStatus::Spam->value;
        }

        if (in_array($to, [
            ContactQualificationStatus::Unqualified,
            ContactQualificationStatus::Archived,
        ], true)) {
            $payload['intake_status'] = LeadIntakeStatus::Closed->value;
        }

        if (array_key_exists('service_interest', $data)) {
            $payload['service_interest'] = $data['service_interest'];
        }

        if (array_key_exists('estimated_value', $data)) {
            $payload['estimated_value'] = $data['estimated_value'];
        }

        if ($payload !== []) {
            $lead->update($payload);
        }
    }
}
