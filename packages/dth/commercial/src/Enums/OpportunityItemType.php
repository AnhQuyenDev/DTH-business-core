<?php

namespace Dth\Commercial\Enums;

use Dth\Commercial\Support\UiText;

enum OpportunityItemType: string
{
    case Product = 'product';
    case Bundle = 'bundle';

    public static function options(): array
    {
        return [
            self::Product->value => UiText::get('opportunity_item_type.product', 'Product'),
            self::Bundle->value => UiText::get('opportunity_item_type.bundle', 'Bundle'),
        ];
    }
}
