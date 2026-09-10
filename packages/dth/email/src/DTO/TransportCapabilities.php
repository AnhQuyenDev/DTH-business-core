<?php

namespace Dth\Email\DTO;

final readonly class TransportCapabilities
{
    public function __construct(
        public array $providers,
        public bool $sent,
        public bool $open,
        public bool $click,
        public bool $unsubscribe,
        public bool $delivery,
        public bool $bounce,
        public bool $complaint,
    ) {}

    public function supports(string $metric): bool
    {
        return match ($metric) {
            'sent' => $this->sent,
            'open', 'opened' => $this->open,
            'click', 'clicked' => $this->click,
            'unsubscribe', 'unsubscribed' => $this->unsubscribe,
            'delivery', 'delivered' => $this->delivery,
            'bounce', 'bounced' => $this->bounce,
            'complaint', 'complained' => $this->complaint,
            default => false,
        };
    }
}
