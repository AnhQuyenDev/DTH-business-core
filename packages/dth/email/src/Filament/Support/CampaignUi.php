<?php

namespace Dth\Email\Filament\Support;

use Dth\Email\Enums\EmailCampaignStatus;
use Dth\Email\Support\UiText;

final class CampaignUi
{
    public static function statusLabel(mixed $state): string
    {
        $value = $state instanceof \BackedEnum
            ? $state->value
            : (string) $state;

        return match ($value) {
            EmailCampaignStatus::Draft->value => UiText::get('campaign.status.draft', 'Bản nháp'),
            EmailCampaignStatus::Scheduled->value => UiText::get('campaign.status.scheduled', 'Đã lên lịch'),
            EmailCampaignStatus::Processing->value => UiText::get('campaign.status.processing', 'Đang gửi'),
            EmailCampaignStatus::Completed->value => UiText::get('campaign.status.completed', 'Hoàn tất'),
            EmailCampaignStatus::Cancelled->value => UiText::get('campaign.status.cancelled', 'Đã hủy'),
            EmailCampaignStatus::Failed->value => UiText::get('campaign.status.failed', 'Thất bại'),
            default => UiText::status($state),
        };
    }

    public static function statusIcon(mixed $state): string
    {
        $value = $state instanceof \BackedEnum
            ? $state->value
            : (string) $state;

        return match ($value) {
            EmailCampaignStatus::Draft->value => 'heroicon-o-document-text',
            EmailCampaignStatus::Scheduled->value => 'heroicon-o-calendar-days',
            EmailCampaignStatus::Processing->value => 'heroicon-o-paper-airplane',
            EmailCampaignStatus::Completed->value => 'heroicon-o-check-circle',
            EmailCampaignStatus::Cancelled->value => 'heroicon-o-no-symbol',
            EmailCampaignStatus::Failed->value => 'heroicon-o-exclamation-circle',
            default => 'heroicon-o-minus-circle',
        };
    }

    public static function statusTone(mixed $state): string
    {
        $value = $state instanceof \BackedEnum
            ? $state->value
            : (string) $state;

        return match ($value) {
            EmailCampaignStatus::Draft->value => 'gray',
            EmailCampaignStatus::Scheduled->value => 'blue',
            EmailCampaignStatus::Processing->value => 'violet',
            EmailCampaignStatus::Completed->value => 'green',
            EmailCampaignStatus::Cancelled->value => 'gray',
            EmailCampaignStatus::Failed->value => 'red',
            default => 'gray',
        };
    }
}
