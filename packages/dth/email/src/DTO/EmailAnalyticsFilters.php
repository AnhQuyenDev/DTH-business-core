<?php

namespace Dth\Email\DTO;

use Dth\Email\Enums\EmailCampaignStatus;
use Dth\Email\Enums\EmailMessageStatus;
use InvalidArgumentException;

final readonly class EmailAnalyticsFilters
{
    public function __construct(
        public AnalyticsRange $range,
        public ?int $sendingAccountId = null,
        public ?int $campaignId = null,
        public EmailMessageStatus|string|null $messageStatus = null,
        public EmailCampaignStatus|string|null $campaignStatus = null,
        public bool $comparePrevious = true,
    ) {
        if ($this->sendingAccountId !== null && $this->sendingAccountId < 1) {
            throw new InvalidArgumentException('Sending account filter must be a positive integer.');
        }

        if ($this->campaignId !== null && $this->campaignId < 1) {
            throw new InvalidArgumentException('Campaign filter must be a positive integer.');
        }
    }

    public static function lastDays(int $days = 30): self
    {
        return new self(AnalyticsRange::lastDays($days));
    }

    public function messageStatusValue(): ?string
    {
        return $this->messageStatus instanceof EmailMessageStatus
            ? $this->messageStatus->value
            : $this->messageStatus;
    }

    public function campaignStatusValue(): ?string
    {
        return $this->campaignStatus instanceof EmailCampaignStatus
            ? $this->campaignStatus->value
            : $this->campaignStatus;
    }

    public function forRange(AnalyticsRange $range): self
    {
        return new self(
            range: $range,
            sendingAccountId: $this->sendingAccountId,
            campaignId: $this->campaignId,
            messageStatus: $this->messageStatus,
            campaignStatus: $this->campaignStatus,
            comparePrevious: false,
        );
    }

    public function cacheKey(): string
    {
        return hash('sha256', json_encode([
            'range' => $this->range->cacheKey(),
            'sending_account_id' => $this->sendingAccountId,
            'campaign_id' => $this->campaignId,
            'message_status' => $this->messageStatusValue(),
            'campaign_status' => $this->campaignStatusValue(),
            'compare_previous' => $this->comparePrevious,
        ], JSON_THROW_ON_ERROR));
    }
}
