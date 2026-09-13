<?php

namespace Dth\Marketing\Support;

use Dth\Marketing\Contracts\AudienceProvider;
use Dth\Marketing\Contracts\CatalogProvider;
use Dth\Marketing\Contracts\EmailMarketingBridge;
use Dth\Marketing\Contracts\LeadProvider;
use Dth\Marketing\Contracts\IntegrationProvider;
use Dth\Marketing\Contracts\RevenueProvider;

final class IntegrationHealthService
{
    public function __construct(
        private readonly AudienceProvider $audience,
        private readonly LeadProvider $lead,
        private readonly CatalogProvider $catalog,
        private readonly RevenueProvider $revenue,
        private readonly EmailMarketingBridge $email,
    ) {}

    /**
     * @return array<string, array{available: bool, capabilities: array<string, bool>}>
     */
    public function snapshot(): array
    {
        return [
            'audience' => $this->status($this->audience),
            'lead' => $this->status($this->lead),
            'catalog' => $this->status($this->catalog),
            'revenue' => $this->status($this->revenue),
            'email' => $this->status($this->email),
        ];
    }

    private function status(IntegrationProvider $provider): array
    {
        return [
            'available' => $provider->available(),
            'capabilities' => $provider->capabilities(),
        ];
    }
}
