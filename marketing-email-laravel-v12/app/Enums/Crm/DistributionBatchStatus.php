<?php

namespace App\Enums\Crm;

enum DistributionBatchStatus: string
{
    case Draft = 'draft';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Reverted = 'reverted';

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
            self::Draft      => __('enum.distribution_batch_status.draft'),
            self::Processing => __('enum.distribution_batch_status.processing'),
            self::Completed  => __('enum.distribution_batch_status.completed'),
            self::Failed     => __('enum.distribution_batch_status.failed'),
            self::Reverted   => __('enum.distribution_batch_status.reverted'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Processing => 'info',
            self::Completed => 'success',
            self::Failed, self::Reverted => 'danger',
        };
    }
}
