<?php

namespace App\Services\Sales;

use App\Enums\Sales\OpportunityStage;
use App\Models\Sales\Opportunity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OpportunityWorkflowService
{
    private const ALLOWED = [
        'discovery' => ['qualified', 'lost', 'cancelled'],
        'qualified' => ['proposal', 'lost', 'cancelled'],
        'proposal' => ['negotiation', 'won', 'lost', 'cancelled'],
        'negotiation' => ['proposal', 'won', 'lost', 'cancelled'],
        'won' => [],
        'lost' => [],
        'cancelled' => [],
    ];

    public function transition(
        Opportunity $opportunity,
        OpportunityStage $to,
        array $data = [],
        ?int $actorUserId = null,
        bool $fromPaymentService = false,
    ): Opportunity {
        return DB::transaction(function () use (
            $opportunity,
            $to,
            $data,
            $actorUserId,
            $fromPaymentService,
        ): Opportunity {
            $locked = Opportunity::query()
                ->whereKey($opportunity->id)
                ->lockForUpdate()
                ->firstOrFail();

            $current = $locked->stage instanceof OpportunityStage
                ? $locked->stage
                : OpportunityStage::from((string) $locked->stage);

            if ($to === $current) {
                return $locked->fresh();
            }

            $this->assertTransitionAllowed($current, $to);
            $this->assertWonRequiresPayment($to, $fromPaymentService);

            $lostReason = $data['lost_reason'] ?? null;

            if ($to === OpportunityStage::Lost && blank($lostReason)) {
                throw ValidationException::withMessages([
                    'lost_reason' => __('validation.opportunity_lost_reason_required'),
                ]);
            }

            $locked->update([
                'stage' => $to->value,
                'probability' => $to->defaultProbability(),
                'expected_close_date' => $data['expected_close_date']
                    ?? $locked->expected_close_date,
                'won_at' => $to === OpportunityStage::Won
                    ? now()
                    : $locked->won_at,
                'lost_at' => $to === OpportunityStage::Lost
                    ? now()
                    : $locked->lost_at,
                'lost_reason' => $to === OpportunityStage::Lost
                    ? $lostReason
                    : ($to === OpportunityStage::Cancelled
                        ? ($data['lost_reason'] ?? $locked->lost_reason)
                        : null),
                'updated_by' => $actorUserId,
            ]);

            return $locked->fresh();
        });
    }

    public function allowedTransitions(OpportunityStage $from): array
    {
        return collect(self::ALLOWED[$from->value] ?? [])
            ->map(
                fn (string $value): OpportunityStage => OpportunityStage::from($value)
            )
            ->all();
    }

    private function assertTransitionAllowed(
        OpportunityStage $from,
        OpportunityStage $to,
    ): void {
        $allowed = self::ALLOWED[$from->value] ?? [];

        if (! in_array($to->value, $allowed, true)) {
            throw ValidationException::withMessages([
                'stage' => __('validation.invalid_opportunity_transition', [
                    'from' => $from->label(),
                    'to' => $to->label(),
                ]),
            ]);
        }
    }

    private function assertWonRequiresPayment(
        OpportunityStage $to,
        bool $fromPaymentService,
    ): void {
        if (
            $to !== OpportunityStage::Won
            || $fromPaymentService
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'stage' => __('validation.opportunity_won_requires_payment'),
        ]);
    }
}
