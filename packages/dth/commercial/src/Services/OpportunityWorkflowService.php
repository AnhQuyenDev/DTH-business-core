<?php

namespace Dth\Commercial\Services;

use Dth\Commercial\Enums\OpportunityStage;
use Dth\Commercial\Models\Opportunity;
use Dth\Commercial\Support\UiText;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OpportunityWorkflowService
{
    private const ALLOWED = [
        'discovery' => ['qualified', 'lost', 'cancelled'],
        'qualified' => ['proposal', 'lost', 'cancelled'],
        'proposal' => ['negotiation', 'won', 'lost', 'cancelled'],
        'negotiation' => ['proposal', 'won', 'lost', 'cancelled'],
        'won' => [], 'lost' => [], 'cancelled' => [],
    ];

    public function allowedTransitions(Opportunity $opportunity): array
    {
        $from = $opportunity->stage instanceof OpportunityStage
            ? $opportunity->stage
            : OpportunityStage::from((string) $opportunity->stage);

        $options = [];
        foreach (self::ALLOWED[$from->value] ?? [] as $value) {
            $stage = OpportunityStage::from($value);
            $options[$value] = $stage->label();
        }

        return $options;
    }

    public function transition(Opportunity $opportunity, OpportunityStage $to, array $data = [], ?int $actorUserId = null): Opportunity
    {
        return DB::transaction(function () use ($opportunity, $to, $data, $actorUserId): Opportunity {
            $locked = Opportunity::query()->whereKey($opportunity->getKey())->lockForUpdate()->firstOrFail();
            $from = $locked->stage instanceof OpportunityStage ? $locked->stage : OpportunityStage::from((string) $locked->stage);

            if ($from === $to) {
                return $locked;
            }

            if (! in_array($to->value, self::ALLOWED[$from->value] ?? [], true)) {
                throw ValidationException::withMessages(['stage' => UiText::get(
                    'validation.invalid_transition',
                    'Invalid opportunity transition: :from -> :to.',
                    ['from' => $from->label(), 'to' => $to->label()],
                )]);
            }

            if ($to === OpportunityStage::Lost && blank($data['lost_reason'] ?? null)) {
                throw ValidationException::withMessages(['lost_reason' => UiText::get(
                    'validation.lost_reason_required',
                    'Lost reason is required.',
                )]);
            }

            $locked->update([
                'stage' => $to->value,
                'probability' => $to->probability(),
                'expected_close_date' => $data['expected_close_date'] ?? $locked->expected_close_date,
                'won_at' => $to === OpportunityStage::Won ? now() : null,
                'lost_at' => $to === OpportunityStage::Lost ? now() : null,
                'lost_reason' => $to === OpportunityStage::Lost ? trim((string) $data['lost_reason']) : null,
                'updated_by' => $actorUserId,
            ]);

            return $locked->fresh();
        });
    }
}
