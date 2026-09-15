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

        return $value === EmailCampaignStatus::Processing->value
            ? UiText::status('sending')
            : UiText::status($state);
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
