<?php

namespace Dth\Email\Services;

use Dth\Email\DTO\TransportCapabilities;

class TransportCapabilityService
{
    public function forProvider(string $provider): TransportCapabilities
    {
        $provider = mb_strtolower(trim($provider));
        $config = (array) config("dth-email.analytics.transport_capabilities.{$provider}", []);

        if ($config === []) {
            $config = (array) config('dth-email.analytics.transport_capabilities.default', []);
        }

        $trackingEnabled = (bool) config('dth-email.tracking.enabled', true);

        return new TransportCapabilities(
            providers: [$provider],
            sent: (bool) ($config['sent'] ?? true),
            open: $trackingEnabled && (bool) ($config['open'] ?? true),
            click: $trackingEnabled && (bool) ($config['click'] ?? true),
            unsubscribe: (bool) ($config['unsubscribe'] ?? true),
            delivery: (bool) ($config['delivery'] ?? false),
            bounce: (bool) ($config['bounce'] ?? false),
            complaint: (bool) ($config['complaint'] ?? false),
        );
    }

    /**
     * A global dashboard can contain messages from more than one transport.
     * A provider-dependent metric is considered fully available only when every
     * provider in the selected cohort supports it. This prevents a mixed cohort
     * from reporting a misleading partial delivery/bounce/complaint rate.
     *
     * @param array<int, string> $providers
     */
    public function combine(array $providers): TransportCapabilities
    {
        $providers = array_values(array_unique(array_filter(array_map(
            static fn ($provider): string => mb_strtolower(trim((string) $provider)),
            $providers,
        ))));

        if ($providers === []) {
            $providers = ['smtp'];
        }

        $items = array_map(
            fn (string $provider): TransportCapabilities => $this->forProvider($provider),
            $providers,
        );

        $every = static function (string $property) use ($items): bool {
            foreach ($items as $item) {
                if (! $item->{$property}) {
                    return false;
                }
            }

            return true;
        };

        return new TransportCapabilities(
            providers: $providers,
            sent: $every('sent'),
            open: $every('open'),
            click: $every('click'),
            unsubscribe: $every('unsubscribe'),
            delivery: $every('delivery'),
            bounce: $every('bounce'),
            complaint: $every('complaint'),
        );
    }
}
