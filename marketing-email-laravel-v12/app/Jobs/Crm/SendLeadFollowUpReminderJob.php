<?php

namespace App\Jobs\Crm;

use App\Models\Crm\Lead;
use App\Models\Marketing\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendLeadFollowUpReminderJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly int $leadId,
    ) {}

    public function handle(): void
    {
        $lead = Lead::query()
            ->with('qualification')
            ->find($this->leadId);

        if ($lead === null || $lead->qualification === null) {
            return;
        }

        $followUpAt = $lead->qualification->next_follow_up_at;

        if ($followUpAt === null || $followUpAt->isFuture()) {
            return;
        }

        $metadata = $lead->metadata ?? [];
        $reminderKey = $followUpAt->toISOString();

        if (($metadata['follow_up_reminder_for'] ?? null) === $reminderKey) {
            return;
        }

        $metadata['follow_up_reminder_for'] = $reminderKey;
        $metadata['follow_up_reminder_recorded_at'] = now()->toISOString();

        $lead->update([
            'metadata' => $metadata,
        ]);

        AuditLog::query()->create([
            'user_id' => null,
            'action' => 'lead.follow_up_due',
            'auditable_type' => Lead::class,
            'auditable_id' => $lead->id,
            'old_values' => [],
            'new_values' => [
                'lead_code' => $lead->lead_code,
                'assigned_staff_id' => $lead->assigned_staff_id,
                'next_follow_up_at' => $followUpAt->toISOString(),
            ],
            'created_at' => now(),
        ]);
    }
}
