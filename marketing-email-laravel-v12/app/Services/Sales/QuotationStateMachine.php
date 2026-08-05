<?php

namespace App\Services\Sales;

use App\Enums\Sales\QuotationStatus;

class QuotationStateMachine
{
    private static array $transitions = [
        'draft' => ['pending_approval', 'approved', 'cancelled'],
        'pending_approval' => ['approved', 'draft', 'cancelled'],
        'approved' => ['sent', 'cancelled'],
        'sent' => ['viewed', 'accepted', 'rejected', 'revision_requested', 'expired', 'cancelled'],
        'viewed' => ['accepted', 'rejected', 'revision_requested', 'expired', 'cancelled'],
        'revision_requested' => ['superseded', 'cancelled'],
    ];

    public function canTransition(QuotationStatus $current, QuotationStatus $target): bool
    {
        $allowed = self::$transitions[$current->value] ?? [];

        return in_array($target->value, $allowed, true);
    }

    public function validateTransition(QuotationStatus $current, QuotationStatus $target): void
    {
        if (! $this->canTransition($current, $target)) {
            throw new \InvalidArgumentException(
                "Cannot transition from {$current->value} to {$target->value}"
            );
        }
    }
}
