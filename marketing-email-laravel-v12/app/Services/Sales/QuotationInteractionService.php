<?php

namespace App\Services\Sales;

use App\Models\Crm\CustomerInteraction;
use App\Models\Sales\Quotation;

class QuotationInteractionService
{
    public function logCreated(Quotation $quotation): CustomerInteraction
    {
        return $this->create($quotation, 'quotation_created', 'Báo giá được tạo');
    }

    public function logSent(Quotation $quotation, string $recipientEmail): CustomerInteraction
    {
        return $this->create($quotation, 'quotation_sent', "Gửi báo giá đến {$recipientEmail}");
    }

    public function logViewed(Quotation $quotation): CustomerInteraction
    {
        return $this->create($quotation, 'quotation_viewed', 'Khách hàng đã xem báo giá');
    }

    public function logAccepted(Quotation $quotation, string $signerName): CustomerInteraction
    {
        return $this->create($quotation, 'quotation_accepted', "Khách hàng {$signerName} đã chấp nhận báo giá");
    }

    public function logAcceptedNotificationSent(Quotation $quotation, string $recipientEmail): CustomerInteraction
    {
        return $this->create($quotation, 'accepted_notification_sent', "Đã gửi thông báo chấp nhận báo giá đến {$recipientEmail}");
    }

    public function logRejected(Quotation $quotation, string $reason = ''): CustomerInteraction
    {
        return $this->create($quotation, 'quotation_rejected', $reason ?: 'Khách hàng đã từ chối báo giá');
    }

    public function logRevisionRequested(Quotation $quotation, string $reason = ''): CustomerInteraction
    {
        return $this->create($quotation, 'quotation_revision_requested', $reason ?: 'Khách hàng yêu cầu chỉnh sửa báo giá');
    }

    public function logPaymentUpdated(Quotation $quotation, string $note = ''): CustomerInteraction
    {
        return $this->create($quotation, 'payment_updated', $note ?: 'Cập nhật trạng thái thanh toán');
    }

    private function create(Quotation $quotation, string $type, string $content): CustomerInteraction
    {
        return CustomerInteraction::query()->create([
            'customer_id' => $quotation->customer_id,
            'staff_id' => $quotation->assigned_staff_id,
            'interaction_type' => $type,
            'subject' => sprintf('[%s] %s', $quotation->quotation_code, $quotation->title),
            'content' => $content,
            'status' => 'completed',
            'interaction_at' => now(),
        ]);
    }
}
