<?php

namespace App\Enums\Crm;

enum LeadActivityType: string
{
    case Call = 'call';
    case Email = 'email';
    case Meeting = 'meeting';
    case Message = 'message';
    case Note = 'note';
    case Other = 'other';

    public static function values(): array
    {
        return array_map(
            static fn (self $case): string => $case->value,
            self::cases(),
        );
    }

    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(
                static fn (self $case): string => $case->label(),
                self::cases(),
            ),
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::Call => __('enum.lead_activity_type.call'),
            self::Email => __('enum.lead_activity_type.email'),
            self::Meeting => __('enum.lead_activity_type.meeting'),
            self::Message => __('enum.lead_activity_type.message'),
            self::Note => __('enum.lead_activity_type.note'),
            self::Other => __('enum.lead_activity_type.other'),
        };
    }

    public function isContactActivity(): bool
    {
        return in_array($this, [
            self::Call,
            self::Email,
            self::Meeting,
            self::Message,
        ], true);
    }
}
