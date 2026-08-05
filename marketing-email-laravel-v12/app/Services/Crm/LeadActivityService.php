<?php

namespace App\Services\Crm;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\LeadActivityStatus;
use App\Enums\Crm\LeadActivityType;
use App\Models\Crm\Lead;
use App\Models\Crm\LeadActivity;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class LeadActivityService
{
    public function __construct(
        private readonly ContactQualificationWorkflowService $workflowService,
    ) {}

    public function record(
        Lead $lead,
        array $data,
        ?int $actorUserId,
        ?int $staffId,
    ): LeadActivity {
        return DB::transaction(function () use (
            $lead,
            $data,
            $actorUserId,
            $staffId,
        ): LeadActivity {
            $lockedLead = Lead::query()
                ->with('qualification')
                ->whereKey($lead->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedLead->qualification === null) {
                throw ValidationException::withMessages([
                    'lead' => __('validation.lead_qualification_missing'),
                ]);
            }

            if ($lockedLead->assigned_staff_id === null) {
                throw ValidationException::withMessages([
                    'lead' => __('validation.lead_must_be_assigned_before_activity'),
                ]);
            }

            $activityType = $data['activity_type'] instanceof LeadActivityType
                ? $data['activity_type']
                : LeadActivityType::from($data['activity_type']);

            $activityStatus = isset($data['status'])
                ? ($data['status'] instanceof LeadActivityStatus
                    ? $data['status']
                    : LeadActivityStatus::from($data['status']))
                : LeadActivityStatus::Completed;

            $activityAt = filled($data['activity_at'] ?? null)
                ? Carbon::parse($data['activity_at'])
                : now();

            $activity = LeadActivity::query()->create([
                'lead_id' => $lockedLead->id,
                'staff_id' => $staffId,
                'created_by_user_id' => $actorUserId,
                'activity_type' => $activityType->value,
                'status' => $activityStatus->value,
                'subject' => $data['subject'],
                'content' => $data['content'] ?? null,
                'outcome' => $data['outcome'] ?? null,
                'activity_at' => $activityAt,
                'next_follow_up_at' => $data['next_follow_up_at'] ?? null,
                'metadata' => Arr::get($data, 'metadata'),
            ]);

            $qualification = $lockedLead->qualification;
            $qualificationPayload = [];

            if (
                $activityType->isContactActivity()
                && $activityStatus === LeadActivityStatus::Completed
            ) {
                $qualificationPayload['first_contacted_at'] =
                    $qualification->first_contacted_at ?? $activityAt;
                $qualificationPayload['last_contacted_at'] = $activityAt;
            }

            if (array_key_exists('next_follow_up_at', $data)) {
                $qualificationPayload['next_follow_up_at'] =
                    $data['next_follow_up_at'];
            }

            if ($qualificationPayload !== []) {
                $qualification->update($qualificationPayload);
            }

            $currentStatus = $qualification->status?->value
                ?? (string) $qualification->status;

            if (
                $activityType->isContactActivity()
                && $activityStatus === LeadActivityStatus::Completed
                && in_array($currentStatus, [
                    ContactQualificationStatus::Assigned->value,
                    ContactQualificationStatus::FollowUp->value,
                ], true)
            ) {
                $this->workflowService->transition(
                    qualification: $qualification->fresh(),
                    to: ContactQualificationStatus::Contacting,
                    data: [
                        'contacted_at' => $activityAt,
                    ],
                    actorUserId: $actorUserId,
                );
            }

            return $activity->fresh([
                'staff',
                'createdBy',
            ]);
        });
    }

    public function scheduleFollowUp(
        Lead $lead,
        string $nextFollowUpAt,
        ?string $note,
        ?int $actorUserId,
        ?int $staffId,
    ): LeadActivity {
        return DB::transaction(function () use (
            $lead,
            $nextFollowUpAt,
            $note,
            $actorUserId,
            $staffId,
        ): LeadActivity {
            $lockedLead = Lead::query()
                ->with('qualification')
                ->whereKey($lead->id)
                ->lockForUpdate()
                ->firstOrFail();

            $current = $lockedLead->qualification?->status?->value
                ?? (string) $lockedLead->qualification?->status;

            if (! in_array($current, [
                ContactQualificationStatus::Contacting->value,
                ContactQualificationStatus::FollowUp->value,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => __('validation.follow_up_requires_contacting'),
                ]);
            }

            if ($current === ContactQualificationStatus::Contacting->value) {
                $this->workflowService->transition(
                    qualification: $lockedLead->qualification,
                    to: ContactQualificationStatus::FollowUp,
                    data: [
                        'next_follow_up_at' => $nextFollowUpAt,
                    ],
                    actorUserId: $actorUserId,
                );
            } else {
                $lockedLead->qualification->update([
                    'next_follow_up_at' => $nextFollowUpAt,
                ]);
            }

            return $this->record(
                lead: $lockedLead,
                data: [
                    'activity_type' => LeadActivityType::Note->value,
                    'status' => LeadActivityStatus::Planned->value,
                    'subject' => __('activity.follow_up_scheduled'),
                    'content' => $note,
                    'activity_at' => now(),
                    'next_follow_up_at' => $nextFollowUpAt,
                ],
                actorUserId: $actorUserId,
                staffId: $staffId,
            );
        });
    }
}
