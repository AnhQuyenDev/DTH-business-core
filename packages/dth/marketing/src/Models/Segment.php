<?php

namespace Dth\Marketing\Models;

use Dth\Marketing\Services\SegmentRuleRegistry;
use Dth\Marketing\Support\UniqueSlug;
use Illuminate\Database\Eloquent\Model;

class Segment extends Model
{
    protected $table = 'marketing_segments';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rules' => 'array',
            'is_automatic' => 'boolean',
            'last_evaluated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $segment): void {
            if ($segment->isDirty('slug') || trim((string) $segment->slug) === '') {
                $segment->slug = UniqueSlug::forModel(
                    $segment,
                    (string) $segment->name,
                    $segment->slug,
                );
            }

            app(SegmentRuleRegistry::class)->assertRuleSetSupported(
                (array) ($segment->rules ?? []),
            );
        });
    }
}
