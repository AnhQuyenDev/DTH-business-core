<?php

namespace Dth\Marketing\DTO;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Arr;

final readonly class MarketingAnalyticsFilter
{
    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
        public CarbonImmutable $previousStart,
        public CarbonImmutable $previousEnd,
        public string $period,
        public ?int $campaignId = null,
        public ?string $campaignStatus = null,
        public ?string $source = null,
    ) {}

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        $period = (string) Arr::get(
            $input,
            'period',
            (string) config('dth-marketing.analytics.default_period', '30d'),
        );
        $today = CarbonImmutable::today();

        [$start, $end] = match ($period) {
            '7d' => [$today->subDays(6)->startOfDay(), $today->endOfDay()],
            '90d' => [$today->subDays(89)->startOfDay(), $today->endOfDay()],
            'month' => [$today->startOfMonth()->startOfDay(), $today->endOfDay()],
            'custom' => self::customRange($input, $today),
            default => [$today->subDays(29)->startOfDay(), $today->endOfDay()],
        };

        $days = max(1, $start->startOfDay()->diffInDays($end->startOfDay()) + 1);
        $previousEnd = $start->subSecond();
        $previousStart = $previousEnd->subDays($days - 1)->startOfDay();

        $campaignId = filter_var(Arr::get($input, 'campaign'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $campaignStatus = trim((string) Arr::get($input, 'status', ''));
        $source = trim((string) Arr::get($input, 'source', ''));

        return new self(
            start: $start,
            end: $end,
            previousStart: $previousStart,
            previousEnd: $previousEnd,
            period: in_array($period, ['7d', '30d', '90d', 'month', 'custom'], true) ? $period : '30d',
            campaignId: $campaignId === false ? null : $campaignId,
            campaignStatus: $campaignStatus !== '' ? $campaignStatus : null,
            source: $source !== '' ? $source : null,
        );
    }

    /** @return array<string, scalar|null> */
    public function toQuery(): array
    {
        return array_filter([
            'period' => $this->period,
            'start' => $this->period === 'custom' ? $this->start->toDateString() : null,
            'end' => $this->period === 'custom' ? $this->end->toDateString() : null,
            'campaign' => $this->campaignId,
            'status' => $this->campaignStatus,
            'source' => $this->source,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    public function label(): string
    {
        return $this->start->format('d/m/Y').' - '.$this->end->format('d/m/Y');
    }

    public function contains(DateTimeInterface $date): bool
    {
        $value = CarbonImmutable::instance($date);

        return $value->betweenIncluded($this->start, $this->end);
    }

    /** @param array<string, mixed> $input @return array{CarbonImmutable,CarbonImmutable} */
    private static function customRange(array $input, CarbonImmutable $today): array
    {
        $startRaw = trim((string) Arr::get($input, 'start', ''));
        $endRaw = trim((string) Arr::get($input, 'end', ''));

        try {
            $start = $startRaw !== '' ? CarbonImmutable::parse($startRaw)->startOfDay() : $today->subDays(29)->startOfDay();
            $end = $endRaw !== '' ? CarbonImmutable::parse($endRaw)->endOfDay() : $today->endOfDay();
        } catch (\Throwable) {
            return [$today->subDays(29)->startOfDay(), $today->endOfDay()];
        }

        if ($end->lt($start)) {
            [$start, $end] = [$end->startOfDay(), $start->endOfDay()];
        }

        // Reporting queries must remain bounded in the admin UI.
        $maxDays = max(1, (int) config('dth-marketing.analytics.max_custom_days', 366));
        if ($start->diffInDays($end) >= $maxDays) {
            $start = $end->subDays($maxDays - 1)->startOfDay();
        }

        return [$start, $end];
    }
}
