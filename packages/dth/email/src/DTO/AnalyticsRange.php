<?php

namespace Dth\Email\DTO;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;

final readonly class AnalyticsRange
{
    public CarbonImmutable $start;
    public CarbonImmutable $end;
    public ?CarbonImmutable $previousStart;
    public ?CarbonImmutable $previousEnd;

    public function __construct(
        DateTimeInterface|string $start,
        DateTimeInterface|string $end,
        bool $withComparison = true,
    ) {
        $this->start = CarbonImmutable::parse($start);
        $this->end = CarbonImmutable::parse($end);

        if ($this->end->lt($this->start)) {
            throw new InvalidArgumentException('Analytics range end must be greater than or equal to start.');
        }

        if ($withComparison) {
            $duration = $this->end->getTimestamp() - $this->start->getTimestamp();
            $previousEnd = $this->start->subSecond();

            $this->previousEnd = $previousEnd;
            $this->previousStart = $previousEnd->subSeconds($duration);
        } else {
            $this->previousStart = null;
            $this->previousEnd = null;
        }
    }

    public static function lastDays(
        int $days = 30,
        DateTimeInterface|string|null $end = null,
        bool $withComparison = true,
    ): self {
        if ($days < 1) {
            throw new InvalidArgumentException('Analytics range days must be at least 1.');
        }

        $resolvedEnd = $end === null
            ? CarbonImmutable::now()->endOfDay()
            : CarbonImmutable::parse($end)->endOfDay();

        return new self(
            start: $resolvedEnd->subDays($days - 1)->startOfDay(),
            end: $resolvedEnd,
            withComparison: $withComparison,
        );
    }

    public static function custom(
        DateTimeInterface|string $start,
        DateTimeInterface|string $end,
        bool $withComparison = true,
    ): self {
        return new self(
            start: CarbonImmutable::parse($start)->startOfDay(),
            end: CarbonImmutable::parse($end)->endOfDay(),
            withComparison: $withComparison,
        );
    }

    public function previous(): ?self
    {
        if ($this->previousStart === null || $this->previousEnd === null) {
            return null;
        }

        return new self(
            start: $this->previousStart,
            end: $this->previousEnd,
            withComparison: false,
        );
    }

    public function days(): int
    {
        return (int) $this->start->startOfDay()->diffInDays($this->end->startOfDay()) + 1;
    }

    public function cacheKey(): string
    {
        return implode(':', [
            $this->start->format('YmdHisP'),
            $this->end->format('YmdHisP'),
            $this->previousStart?->format('YmdHisP') ?? 'none',
            $this->previousEnd?->format('YmdHisP') ?? 'none',
        ]);
    }
}
