<?php

namespace App\Jobs\Crm;

use App\Enums\Crm\ContactQualificationStatus;
use App\Models\Crm\Lead;
use App\Models\Marketing\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class MarkStaleLeadsForReviewJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly int $staleAfterDays = 3,
    ) {}

    public function handle(): void
    {
        Lead::query()
            ->whereNotNull('assigned_staff_id')
            ->whereHas('qualification', function ($query): void {
                $query->whereIn('status', [
                    ContactQualificationStatus::Assigned->value,
                    ContactQualificationStatus::Contacting->value,
                    ContactQualificationStatus::FollowUp->value,
                ]);
            })
            ->with('qualification')
            ->orderBy('id')
            ->chunkById(100, function ($leads): void {
                foreach ($leads as $lead) {
                    $lastActivityAt = $lead->activities()
                        ->max('activity_at');

                    $referenceAt = $lastActivityAt
                        ? Carbon::parse($lastActivityAt)
                        : ($lead->qualification->last_contacted_at
                            ?? $lead->assigned_at
                            ?? $lead->created_at);

                    if ($referenceAt->gt(now()->subDays($this->staleAfterDays))) {
                        continue;
                    }

                    $metadata = $lead->metadata ?? [];
                    $marker = now()->toDateString();

                    if (($metadata['stale_review_marker'] ?? null) === $marker) {
                        continue;
                    }

                    $metadata['stale_review_required_at'] = now()->toISOString();
                    $metadata['stale_review_marker'] = $marker;

                    $lead->update([
                        'metadata' => $metadata,
                    ]);

                    AuditLog::query()->create([
                        'user_id' => null,
                        'action' => 'lead.stale_review_required',
                        'auditable_type' => Lead::class,
                        'auditable_id' => $lead->id,
                        'old_values' => [],
                        'new_values' => [
                            'lead_code' => $lead->lead_code,
                            'assigned_staff_id' => $lead->assigned_staff_id,
                            'last_activity_at' => $referenceAt->toISOString(),
                        ],
                        'created_at' => now(),
                    ]);
                }
            });
    }
}
