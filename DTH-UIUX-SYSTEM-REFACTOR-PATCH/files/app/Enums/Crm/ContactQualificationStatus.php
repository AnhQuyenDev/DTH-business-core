<?php

namespace App\Enums\Crm;

use App\Support\Ui\BadgePalette;

enum ContactQualificationStatus: string
{
    case New = 'new';
    case Assigned = 'assigned';
    case Contacting = 'contacting';
    case FollowUp = 'follow_up';
    case Qualified = 'qualified';
    case Unqualified = 'unqualified';
    case Converted = 'converted';
    case Duplicate = 'duplicate';
    case Spam = 'spam';
    case Archived = 'archived';

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
            self::New => __('enum.qualification.new'),
            self::Assigned => __('enum.qualification.assigned'),
            self::Contacting => __('enum.qualification.contacting'),
            self::FollowUp => __('enum.qualification.follow_up'),
            self::Qualified => __('enum.qualification.qualified'),
            self::Unqualified => __('enum.qualification.unqualified'),
            self::Converted => __('enum.qualification.converted'),
            self::Duplicate => __('enum.qualification.duplicate'),
            self::Spam => __('enum.qualification.spam'),
            self::Archived => __('enum.qualification.archived'),
        };
    }

    public function isConvertible(): bool
    {
        return $this === self::Qualified;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Unqualified,
            self::Converted,
            self::Duplicate,
            self::Spam,
            self::Archived,
        ], true);
    }

    public function isOpen(): bool
    {
        return in_array($this, [
            self::New,
            self::Assigned,
            self::Contacting,
            self::FollowUp,
            self::Qualified,
        ], true);
    }

    public function color(): string
    {
        return BadgePalette::status($this);
    }

}
