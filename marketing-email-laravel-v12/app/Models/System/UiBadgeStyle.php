<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class UiBadgeStyle extends Model
{
    protected $fillable = [
        'category',
        'key',
        'color',
    ];

    protected static function booted(): void
    {
        $forget = static function (self $style): void {
            Cache::forget('ui.badge.'.$style->category.'.'.$style->key);
        };

        static::saved($forget);
        static::deleted($forget);
    }
}
