<?php

namespace Dth\Email\DTO;

final readonly class AnalyticsMetricDelta
{
    public function __construct(
        public string $metric,
        public int|float|null $current,
        public int|float|null $previous,
        public ?float $absoluteChange,
        public ?float $percentChange,
        public string $direction,
    ) {}
}
