<?php

namespace App\Listeners\Crm;

use App\Events\Crm\LeadAssigned;
use App\Models\Crm\Lead;
use App\Models\Marketing\AuditLog;

final class RecordLeadAssignmentAudit
{
    public function handle(LeadAssigned $event): void
    {
        AuditLog::query()->create([
            'user_id' => $event->assignedByUserId,
            'action' => $event->fromStaffId === null
                ? 'lead.assigned'
                : 'lead.reassigned',
            'auditable_type' => Lead::class,
            'auditable_id' => $event->leadId,
            'old_values' => [
                'assigned_staff_id' => $event->fromStaffId,
            ],
            'new_values' => [
                'assigned_staff_id' => $event->toStaffId,
                'reason' => $event->reason,
                'forced' => $event->forced,
                'company_owner_transferred' => $event->companyOwnerTransferred,
            ],
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
