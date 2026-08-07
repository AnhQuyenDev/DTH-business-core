<?php

namespace App\Services\Sales;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Sales\OpportunityStage;
use App\Models\Crm\Lead;
use App\Models\Crm\Staff;
use App\Models\Sales\Opportunity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class OpportunityCreationService
{
    public function createFromQualifiedLead(
        Lead $lead,
        Staff $salesOwner,
        array $data,
        int $actorUserId,
    ): Opportunity {
        return DB::transaction(function () use (
            $lead,
            $salesOwner,
            $data,
            $actorUserId,
        ): Opportunity {
            $lockedLead = Lead::query()
                ->with([
                    'qualification',
                    'company',
                    'contact',
                    'opportunity',
                ])
                ->whereKey($lead->id)
                ->lockForUpdate()
                ->firstOrFail();

            $status = $lockedLead->qualification?->status;

            $statusValue = $status instanceof ContactQualificationStatus
                ? $status->value
                : $status;

            if (
                $statusValue
                !== ContactQualificationStatus::Qualified->value
            ) {
                throw ValidationException::withMessages([
                    'lead' => 'Chỉ Lead đã đủ điều kiện mới được bàn giao sang Sales.',
                ]);
            }

            /*
             * Idempotency:
             * một Lead chỉ có một Opportunity.
             */
            $existing = Opportunity::query()
                ->where('lead_id', $lockedLead->id)
                ->first();

            if ($existing !== null) {
                throw ValidationException::withMessages([
                    'lead' => 'Lead này đã có cơ hội kinh doanh.',
                ]);
            }

            /*
             * Backend kiểm tra Sales Owner,
             * không chỉ tin dropdown Filament.
             */
            $validSalesOwner = Staff::query()
                ->eligibleForOpportunityOwnership()
                ->whereKey($salesOwner->id)
                ->exists();

            if (! $validSalesOwner) {
                throw ValidationException::withMessages([
                    'sales_owner' => 'Nhân viên được chọn không đủ điều kiện nhận cơ hội kinh doanh.',
                ]);
            }

            $opportunity = Opportunity::query()->create([
                'opportunity_code' => $this->generateCode(),

                'lead_id' => $lockedLead->id,

                'company_id' => $lockedLead->company_id,

                'primary_contact_id' => $lockedLead->contact_id,

                'assigned_staff_id' => $salesOwner->id,

                'title' => $data['title'],

                /*
                 * Giữ mã kỹ thuật của service/package.
                 */
                'service_interest' =>
                    $lockedLead->service_interest,

                /*
                 * Giá trị Qualification là giá trị bàn giao mặc định.
                 */
                'estimated_value' =>
                    $lockedLead->estimated_value,

                /*
                 * Nếu enum local của bạn dùng tên case khác,
                 * thay Qualified bằng stage khởi đầu tương ứng.
                 */
                'stage' => OpportunityStage::Qualified->value,

                'probability' =>
                    (int) ($data['probability'] ?? 50),

                'expected_close_date' =>
                    $data['expected_close_date'] ?? null,

                /*
                 * Snapshot thông tin bàn giao.
                 */
                'metadata' => [
                    'handoff' => [
                        'from_lead_code' =>
                            $lockedLead->lead_code,

                        'handoff_at' =>
                            now()->toISOString(),

                        'handoff_by_user_id' =>
                            $actorUserId,

                        'customer_service_staff_id' =>
                            $lockedLead->assigned_staff_id,

                        'sales_staff_id' =>
                            $salesOwner->id,

                        'qualification' => [
                            'qualified_at' =>
                                $lockedLead
                                    ->qualification
                                    ?->qualified_at
                                    ?->toISOString(),

                            'qualified_by_staff_id' =>
                                $lockedLead
                                    ->qualification
                                    ?->qualified_by_staff_id,

                            'priority' =>
                                $lockedLead
                                    ->qualification
                                    ?->priority,

                            'score' =>
                                $lockedLead
                                    ->qualification
                                    ?->score,

                            'budget_status' =>
                                $lockedLead
                                    ->qualification
                                    ?->budget_status,

                            'budget_amount' =>
                                $lockedLead
                                    ->qualification
                                    ?->budget_amount,

                            'purchase_timeline' =>
                                $lockedLead
                                    ->qualification
                                    ?->purchase_timeline,

                            'decision_role' =>
                                $lockedLead
                                    ->qualification
                                    ?->decision_role,

                            'qualification_note' =>
                                $lockedLead
                                    ->qualification
                                    ?->qualification_note,
                        ],

                        'service_context' =>
                            data_get(
                                $lockedLead->metadata,
                                'service_context'
                            ),

                        'note' =>
                            $data['handoff_note'] ?? null,
                    ],
                ],

                'created_by' => $actorUserId,
                'updated_by' => $actorUserId,
            ]);

            if ($opportunity->wasRecentlyCreated) {
                $opportunity->contacts()->syncWithoutDetaching([
                    $lead->contact_id => [
                        'role' => 'primary_contact',
                        'is_primary' => true,
                    ],
                ]);

                $lockedLead->forceFill([
                    'converted_to_opportunity_at' => now(),
                    'updated_by' => $actorUserId,
                ])->save();  
            }

            return $opportunity->fresh([
                'lead',
                'company',
                'primaryContact',
                'assignedStaff',
            ]);
        }, 3);
    }

    private function generateCode(): string
    {
        $prefix = 'OPP-'.now()->format('Y').'-';

        $lastCode = Opportunity::withTrashed()
            ->where(
                'opportunity_code',
                'like',
                $prefix.'%'
            )
            ->lockForUpdate()
            ->orderByDesc('opportunity_code')
            ->value('opportunity_code');

        $lastNumber = $lastCode
            ? (int) Str::afterLast($lastCode, '-')
            : 0;

        return $prefix.str_pad(
            (string) ($lastNumber + 1),
            6,
            '0',
            STR_PAD_LEFT
        );
    }
}