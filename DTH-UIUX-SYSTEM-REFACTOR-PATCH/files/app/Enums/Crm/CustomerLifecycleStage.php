<?php

namespace App\Enums\Crm;

use App\Support\Ui\BadgePalette;

enum CustomerLifecycleStage: string
{
    case NewCustomer = 'new_customer';
    case Onboarding = 'onboarding';
    case Nurturing = 'nurturing';
    case Purchasing = 'purchasing';
    case Retained = 'retained';
    case AtRisk = 'at_risk';
    case Churned = 'churned';

    public static function values(): array
    {
        return array_map(static fn (self $v) => $v->value, self::cases());
    }

    public static function options(): array
    {
        return array_combine(self::values(), array_map(static fn (self $v) => $v->label(), self::cases()));
    }

    public function label(): string
    {
        return match ($this) {
            self::NewCustomer => __('enum.lifecycle.new_customer'),
            self::Onboarding => __('enum.lifecycle.onboarding'),
            self::Nurturing => __('enum.lifecycle.nurturing'),
            self::Purchasing => __('enum.lifecycle.purchasing'),
            self::Retained => __('enum.lifecycle.retained'),
            self::AtRisk => __('enum.lifecycle.at_risk'),
            self::Churned => __('enum.lifecycle.churned'),
        };
    }

    public function color(): string
    {
        return BadgePalette::status($this);
    }

}
