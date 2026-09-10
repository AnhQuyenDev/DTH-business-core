<?php

namespace Dth\Email\DTO;

final readonly class EmailAnalyticsOverview
{
    /**
     * @param array<string, AnalyticsMetricDelta> $deltas
     */
    public function __construct(
        public AnalyticsRange $range,
        public EmailAnalyticsSnapshot $current,
        public ?EmailAnalyticsSnapshot $previous,
        public array $deltas,
        public TransportCapabilities $capabilities,
    ) {}
}
