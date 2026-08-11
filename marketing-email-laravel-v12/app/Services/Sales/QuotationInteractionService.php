<?php

namespace App\Services\Sales;

use App\Models\Crm\CustomerInteraction;
use App\Models\Sales\OpportunityInteraction;
use App\Models\Sales\Quotation;
use App\Models\Sales\QuotationInteraction;
use Illuminate\Database\Eloquent\Model;

class QuotationInteractionService
{
    public function logCreated(Quotation $quotation): Model
    {
        return $this->create(
            $quotation,
            'quotation_created',
            __('activity.message.quotation_created'),
        );
    }

    public function logSent(
        Quotation $quotation,
        string $recipientEmail,
    ): Model {
        return $this->create(
            $quotation,
            'quotation_sent',
            __('activity.message.quotation_sent', ['email' => $recipientEmail]),
        );
    }

    public function logViewed(Quotation $quotation): Model
    {
        return $this->create(
            $quotation,
            'quotation_viewed',
            __('activity.message.quotation_viewed'),
        );
    }

    public function logAccepted(
        Quotation $quotation,
        string $signerName,
    ): Model {
        return $this->create(
            $quotation,
            'quotation_accepted',
            __('activity.message.quotation_accepted', ['name' => $signerName]),
        );
    }

    public function logAcceptedNotificationSent(
        Quotation $quotation,
        string $recipientEmail,
    ): Model {
        return $this->create(
            $quotation,
            'accepted_notification_sent',
            __('activity.message.accepted_notification_sent', ['email' => $recipientEmail]),
        );
    }

    public function logRejected(
        Quotation $quotation,
        string $reason = '',
    ): Model {
        return $this->create(
            $quotation,
            'quotation_rejected',
            $reason ?: __('activity.message.quotation_rejected'),
        );
    }

    public function logRevisionRequested(
        Quotation $quotation,
        string $reason = '',
    ): Model {
        return $this->create(
            $quotation,
            'quotation_revision_requested',
            $reason ?: __('activity.message.quotation_revision_requested'),
        );
    }

    public function logPaymentUpdated(
        Quotation $quotation,
        string $note = '',
    ): Model {
        return $this->create(
            $quotation,
            'payment_updated',
            $note ?: __('activity.message.payment_updated'),
        );
    }

    public function logFollowUpScheduled(
        Quotation $quotation,
        string $note,
    ): Model {
        return $this->create(
            $quotation,
            'quotation_follow_up',
            $note,
        );
    }

    private function create(
        Quotation $quotation,
        string $type,
        string $content,
    ): Model {
        if ($quotation->customer_id !== null) {
            return CustomerInteraction::query()->create([
                'customer_id' => $quotation->customer_id,
                'staff_id' => $quotation->assigned_staff_id,
                'interaction_type' => $type,
                'subject' => sprintf(
                    '[%s] %s',
                    $quotation->quotation_code,
                    $quotation->title,
                ),
                'content' => $content,
                'status' => 'completed',
                'interaction_at' => now(),
            ]);
        }

        if ($quotation->opportunity_id !== null) {
            return OpportunityInteraction::query()->create([
                'opportunity_id' => $quotation->opportunity_id,
                'staff_id' => $quotation->assigned_staff_id,
                'interaction_type' => $type,
                'subject' => sprintf(
                    '[%s] %s',
                    $quotation->quotation_code,
                    $quotation->title,
                ),
                'content' => $content,
                'outcome' => null,
                'interaction_at' => now(),
                'metadata' => [
                    'quotation_id' => $quotation->id,
                    'quotation_code' => $quotation->quotation_code,
                ],
            ]);
        }

        // Báo giá chỉ có customer_snapshot (chưa có Customer hay
        // Opportunity) vẫn phải ghi nhận tương tác công khai.
        return QuotationInteraction::query()->create([
            'quotation_id' => $quotation->id,
            'staff_id' => $quotation->assigned_staff_id,
            'interaction_type' => $type,
            'subject' => sprintf(
                '[%s] %s',
                $quotation->quotation_code,
                $quotation->title,
            ),
            'content' => $content,
            'outcome' => null,
            'interaction_at' => now(),
            'metadata' => [
                'quotation_id' => $quotation->id,
                'quotation_code' => $quotation->quotation_code,
            ],
        ]);
    }
}
