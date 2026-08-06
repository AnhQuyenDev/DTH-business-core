<?php

namespace App\Services\Sales;

use App\Models\Crm\CustomerInteraction;
use App\Models\Sales\OpportunityInteraction;
use App\Models\Sales\Quotation;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class QuotationInteractionService
{
    public function logCreated(Quotation $quotation): Model
    {
        return $this->create(
            $quotation,
            'quotation_created',
            'Báo giá được tạo',
        );
    }

    public function logSent(
        Quotation $quotation,
        string $recipientEmail,
    ): Model {
        return $this->create(
            $quotation,
            'quotation_sent',
            "Gửi báo giá đến {$recipientEmail}",
        );
    }

    public function logViewed(Quotation $quotation): Model
    {
        return $this->create(
            $quotation,
            'quotation_viewed',
            'Người nhận đã xem báo giá',
        );
    }

    public function logAccepted(
        Quotation $quotation,
        string $signerName,
    ): Model {
        return $this->create(
            $quotation,
            'quotation_accepted',
            "{$signerName} đã chấp nhận báo giá",
        );
    }

    public function logAcceptedNotificationSent(
        Quotation $quotation,
        string $recipientEmail,
    ): Model {
        return $this->create(
            $quotation,
            'accepted_notification_sent',
            "Đã gửi thông báo chấp nhận đến {$recipientEmail}",
        );
    }

    public function logRejected(
        Quotation $quotation,
        string $reason = '',
    ): Model {
        return $this->create(
            $quotation,
            'quotation_rejected',
            $reason ?: 'Người nhận đã từ chối báo giá',
        );
    }

    public function logRevisionRequested(
        Quotation $quotation,
        string $reason = '',
    ): Model {
        return $this->create(
            $quotation,
            'quotation_revision_requested',
            $reason ?: 'Người nhận yêu cầu chỉnh sửa báo giá',
        );
    }

    public function logPaymentUpdated(
        Quotation $quotation,
        string $note = '',
    ): Model {
        return $this->create(
            $quotation,
            'payment_updated',
            $note ?: 'Cập nhật trạng thái thanh toán',
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

        throw new LogicException(
            'Quotation has neither customer nor opportunity.'
        );
    }
}
