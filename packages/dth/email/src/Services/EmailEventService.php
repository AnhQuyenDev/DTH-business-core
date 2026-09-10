<?php

namespace Dth\Email\Services;

use DateTimeInterface;
use Dth\Email\Enums\CampaignRecipientStatus;
use Dth\Email\Enums\EmailEventType;
use Dth\Email\Enums\EmailMessageStatus;
use Dth\Email\Enums\SuppressionReason;
use Dth\Email\Models\CampaignRecipient;
use Dth\Email\Models\EmailEvent;
use Dth\Email\Models\EmailMessage;
use Dth\Email\Models\EmailTrackedLink;
use Illuminate\Support\Facades\DB;

class EmailEventService
{
    public function __construct(
        private readonly SuppressionService $suppressions,
    ) {}

    public function record(
        EmailMessage $message,
        EmailEventType $type,
        array $payload = [],
        ?string $providerEventId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?DateTimeInterface $occurredAt = null,
    ): EmailEvent {
        $occurredAt ??= now();

        $data = [
            'message_id' => $message->id,
            'event_type' => $type,
            'payload' => $payload ?: null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'occurred_at' => $occurredAt,
        ];

        if ($providerEventId !== null) {
            return EmailEvent::query()->firstOrCreate(
                [
                    'provider_event_id' => $providerEventId,
                ],
                $data,
            );
        }

        return EmailEvent::query()->create($data);
    }

    public function opened(
        EmailMessage $message,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): EmailEvent {
        return DB::transaction(function () use (
            $message,
            $ipAddress,
            $userAgent,
        ): EmailEvent {
            $at = now();

            $event = $this->record(
                message: $message,
                type: EmailEventType::Opened,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                occurredAt: $at,
            );

            if ($recipient = $message->campaignRecipient) {
                $recipient->update([
                    'opened_at' => $recipient->opened_at ?? $at,
                ]);

                $this->promoteRecipientStatus(
                    $recipient,
                    CampaignRecipientStatus::Opened,
                );
            }

            return $event;
        });
    }

    public function clicked(
        EmailTrackedLink $link,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): EmailEvent {
        return DB::transaction(function () use (
            $link,
            $ipAddress,
            $userAgent,
        ): EmailEvent {
            $at = now();

            $link->increment('click_count');

            $link->update([
                'last_clicked_at' => $at,
            ]);

            $message = $link->message;

            $event = $this->record(
                message: $message,
                type: EmailEventType::Clicked,
                payload: [
                    'url' => $link->original_url,
                ],
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                occurredAt: $at,
            );

            if ($recipient = $message->campaignRecipient) {
                $recipient->update([
                    'clicked_at' => $recipient->clicked_at ?? $at,
                ]);

                $this->promoteRecipientStatus(
                    $recipient,
                    CampaignRecipientStatus::Clicked,
                );
            }

            return $event;
        });
    }

    public function delivered(
        EmailMessage $message,
        ?string $providerEventId = null,
        array $payload = [],
    ): EmailEvent {
        return DB::transaction(function () use (
            $message,
            $providerEventId,
            $payload,
        ): EmailEvent {
            $at = now();

            if (! in_array(
                $message->status,
                [
                    EmailMessageStatus::Failed,
                    EmailMessageStatus::Bounced,
                    EmailMessageStatus::Complained,
                    EmailMessageStatus::Suppressed,
                ],
                true,
            )) {
                $message->update([
                    'status' => EmailMessageStatus::Delivered,
                    'delivered_at' => $message->delivered_at ?? $at,
                ]);
            }

            if ($recipient = $message->campaignRecipient) {
                $this->promoteRecipientStatus(
                    $recipient,
                    CampaignRecipientStatus::Delivered,
                );
            }

            return $this->record(
                message: $message,
                type: EmailEventType::Delivered,
                payload: $payload,
                providerEventId: $providerEventId,
                occurredAt: $at,
            );
        });
    }

    public function bounced(
        EmailMessage $message,
        ?string $providerEventId = null,
        array $payload = [],
    ): EmailEvent {
        return DB::transaction(function () use (
            $message,
            $providerEventId,
            $payload,
        ): EmailEvent {
            $at = now();

            $reason = (string) (
                $payload['reason']
                ?? 'Email bounced.'
            );

            $message->update([
                'status' => EmailMessageStatus::Bounced,
                'failed_at' => $at,
                'failure_reason' => mb_substr($reason, 0, 5000),
            ]);

            if ($recipient = $message->campaignRecipient) {
                $recipient->update([
                    'status' => CampaignRecipientStatus::Bounced,
                    'failed_at' => $at,
                    'failure_reason' => mb_substr($reason, 0, 5000),
                ]);
            }

            $this->suppressions->suppress(
                email: $message->recipient_email,
                reason: SuppressionReason::Bounce,
                source: 'email.event.bounce',
                message: $message,
            );

            return $this->record(
                message: $message,
                type: EmailEventType::Bounced,
                payload: $payload,
                providerEventId: $providerEventId,
                occurredAt: $at,
            );
        });
    }

    public function complained(
        EmailMessage $message,
        ?string $providerEventId = null,
        array $payload = [],
    ): EmailEvent {
        return DB::transaction(function () use (
            $message,
            $providerEventId,
            $payload,
        ): EmailEvent {
            $message->update([
                'status' => EmailMessageStatus::Complained,
            ]);

            if ($recipient = $message->campaignRecipient) {
                $recipient->update([
                    'status' => CampaignRecipientStatus::Complained,
                ]);
            }

            $this->suppressions->suppress(
                email: $message->recipient_email,
                reason: SuppressionReason::Complaint,
                source: 'email.event.complaint',
                message: $message,
            );

            return $this->record(
                message: $message,
                type: EmailEventType::Complained,
                payload: $payload,
                providerEventId: $providerEventId,
            );
        });
    }

    public function unsubscribed(
        EmailMessage $message,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): EmailEvent {
        return DB::transaction(function () use (
            $message,
            $ipAddress,
            $userAgent,
        ): EmailEvent {
            $this->suppressions->suppress(
                email: $message->recipient_email,
                reason: SuppressionReason::Unsubscribe,
                source: 'email.unsubscribe',
                message: $message,
            );

            if ($recipient = $message->campaignRecipient) {
                $recipient->update([
                    'status' => CampaignRecipientStatus::Unsubscribed,
                ]);
            }

            return $this->record(
                message: $message,
                type: EmailEventType::Unsubscribed,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );
        });
    }

    private function promoteRecipientStatus(
        CampaignRecipient $recipient,
        CampaignRecipientStatus $target,
    ): void {
        $currentRank = $this->engagementRank(
            $recipient->status
        );

        $targetRank = $this->engagementRank(
            $target
        );

        if (
            $currentRank === null
            || $targetRank === null
            || $targetRank <= $currentRank
        ) {
            return;
        }

        $recipient->update([
            'status' => $target,
        ]);
    }

    private function engagementRank(
        CampaignRecipientStatus $status,
    ): ?int {
        return match ($status) {
            CampaignRecipientStatus::Pending => 0,
            CampaignRecipientStatus::Queued => 1,
            CampaignRecipientStatus::Sent => 2,
            CampaignRecipientStatus::Delivered => 3,
            CampaignRecipientStatus::Opened => 4,
            CampaignRecipientStatus::Clicked => 5,

            default => null,
        };
    }
}