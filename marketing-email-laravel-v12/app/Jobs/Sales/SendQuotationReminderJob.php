<?php

namespace App\Jobs\Sales;

use App\Services\Sales\QuotationReminderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendQuotationReminderJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct() {}

    public function handle(QuotationReminderService $reminder): void
    {
        $count = 0;

        foreach ($reminder->getSentNotViewed() as $q) {
            $reminder->createFollowUpTask($q, "Đã gửi báo giá {$q->quotation_code} nhưng chưa xem.");
            $count++;
        }

        foreach ($reminder->getViewedNotResponded() as $q) {
            $reminder->createFollowUpTask($q, "Khách đã xem báo giá {$q->quotation_code} nhưng chưa phản hồi.");
            $count++;
        }

        foreach ($reminder->getExpiringSoon() as $q) {
            $reminder->createFollowUpTask($q, "Báo giá {$q->quotation_code} sắp hết hạn.");
            SendQuotationExpiringNotificationJob::dispatch($q);
            $count++;
        }

        foreach ($reminder->getAcceptedUnpaid() as $q) {
            $reminder->createFollowUpTask($q, "Báo giá {$q->quotation_code} đã accepted nhưng chưa thanh toán.");
            $count++;
        }

        foreach ($reminder->getRevisionRequested() as $q) {
            $reminder->createFollowUpTask($q, "Báo giá {$q->quotation_code} đang chờ xử lý yêu cầu chỉnh sửa.");
            $count++;
        }

        Log::info('SendQuotationReminderJob: created follow-up tasks', ['count' => $count]);
    }
}
