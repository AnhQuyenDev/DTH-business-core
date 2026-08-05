<?php

namespace App\Enums\Marketing;

enum LandingPageSubmissionStatus: string
{
    case Received = 'received';
    case Processed = 'processed';
    case Failed = 'failed';
    case Spam = 'spam';

    public static function values(): array
    {
        return array_map(static fn (self $s) => $s->value, self::cases());
    }

    public static function options(): array
    {
        return array_combine(self::values(), array_map(static fn (self $s) => $s->label(), self::cases()));
    }

    public function label(): string
    {
        return match ($this) {
            self::Received => __('enum.landing_page_submission.received'),
            self::Processed => __('enum.landing_page_submission.processed'),
            self::Failed => __('enum.landing_page_submission.failed'),
            self::Spam => __('enum.landing_page_submission.spam'),
        };
    }
}
