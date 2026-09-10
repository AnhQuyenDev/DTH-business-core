<?php

namespace Dth\Email\Services;

use Carbon\CarbonImmutable;
use Dth\Email\DTO\AnalyticsRange;
use Dth\Email\DTO\EmailAnalyticsFilters;
use Dth\Email\Enums\EmailCampaignStatus;
use Dth\Email\Enums\EmailMessageStatus;
use Throwable;

final class EmailDashboardFilterResolver
{
    /**
     * Normalize validated Filament dashboard filter state into the Step B DTO.
     * Missing or stale session values fall back safely to the default range.
     *
     * @param array<string, mixed>|null $state
     */
    public function resolve(?array $state): EmailAnalyticsFilters
    {
        $state ??= [];
        $comparePrevious = filter_var(
            $state['compare_previous'] ?? true,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE,
        ) ?? true;

        $range = $this->resolveRange($state, $comparePrevious);

        return new EmailAnalyticsFilters(
            range: $range,
            sendingAccountId: $this->positiveInt($state['sending_account_id'] ?? null),
            campaignId: $this->positiveInt($state['campaign_id'] ?? null),
            messageStatus: $this->enumValue(EmailMessageStatus::class, $state['message_status'] ?? null),
            campaignStatus: $this->enumValue(EmailCampaignStatus::class, $state['campaign_status'] ?? null),
            comparePrevious: $comparePrevious,
        );
    }

    /** @param array<string, mixed> $state */
    private function resolveRange(array $state, bool $comparePrevious): AnalyticsRange
    {
        $defaultDays = max(1, (int) config('dth-email.analytics.default_range_days', 30));
        $start = $state['start_date'] ?? null;
        $end = $state['end_date'] ?? null;

        if (! filled($start) || ! filled($end)) {
            return AnalyticsRange::lastDays($defaultDays, withComparison: $comparePrevious);
        }

        try {
            $startDate = CarbonImmutable::parse((string) $start)->startOfDay();
            $endDate = CarbonImmutable::parse((string) $end)->endOfDay();

            if ($endDate->lt($startDate)) {
                return AnalyticsRange::lastDays($defaultDays, withComparison: $comparePrevious);
            }

            return AnalyticsRange::custom(
                start: $startDate,
                end: $endDate,
                withComparison: $comparePrevious,
            );
        } catch (Throwable) {
            return AnalyticsRange::lastDays($defaultDays, withComparison: $comparePrevious);
        }
    }

    private function positiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = filter_var($value, FILTER_VALIDATE_INT);

        return is_int($value) && $value > 0 ? $value : null;
    }

    /**
     * @template T of \BackedEnum
     * @param class-string<T> $enumClass
     */
    private function enumValue(string $enumClass, mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $enumClass::tryFrom($value)?->value;
    }
}
