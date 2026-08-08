<?php

namespace App\Services\Sales;

use App\Enums\Sales\QuotationStatus;
use Illuminate\Validation\ValidationException;

class QuotationStateMachine
{
    private static array $transitions = [
        // Every commercial quotation follows maker-checker approval.
        'draft' => ['pending_approval', 'cancelled'],
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
            throw ValidationException::withMessages([
                'status' => sprintf(
                    'Không thể chuyển báo giá từ %s sang %s.',
                    $current->label(),
                    $target->label(),
                ),
            ]);
        }
    }
}
