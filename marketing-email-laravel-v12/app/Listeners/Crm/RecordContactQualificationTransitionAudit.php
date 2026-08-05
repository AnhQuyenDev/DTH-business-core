<?php

namespace App\Listeners\Crm;

use App\Events\Crm\ContactQualificationTransitioned;
use App\Models\Crm\Lead;
use App\Models\Marketing\AuditLog;

final class RecordContactQualificationTransitionAudit
{
    public function handle(
        ContactQualificationTransitioned $event
    ): void {
        AuditLog::query()->create([
            'user_id' => $event->actorUserId,
            'action' => 'lead.qualification_transitioned',
            'auditable_type' => Lead::class,
            'auditable_id' => $event->leadId,
            'old_values' => [
                'qualification_status' => $event->fromStatus,
            ],
            'new_values' => array_merge([
                'qualification_status' => $event->toStatus,
                'qualification_id' => $event->qualificationId,
            ], $event->changedValues),
            'ip_address' => app()->runningInConsole()
                ? null
                : request()->ip(),
            'user_agent' => app()->runningInConsole()
                ? null
                : substr((string) request()->userAgent(), 0, 1000),
            'created_at' => now(),
        ]);
    }
}
