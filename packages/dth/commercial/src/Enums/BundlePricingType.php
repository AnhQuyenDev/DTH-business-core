<?php

namespace Dth\Commercial\Enums;

use Dth\Commercial\Support\UiText;

enum BundlePricingType: string
{
    case ComponentSum = 'component_sum';
    case Fixed = 'fixed';

    public static function options(): array
    {
        return [
            self::ComponentSum->value => UiText::get('bundle_pricing.component_sum', 'Sum component prices'),
            self::Fixed->value => UiText::get('bundle_pricing.fixed', 'Fixed bundle price'),
        ];
    }
}
