<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemTranslation extends Model
{
    protected $fillable = [
        'translation_key',
        'locale',
        'value',
        'default_value',
        'module',
        'context',
        'source',
        'is_reviewed',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'is_reviewed' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn (self $translation) => self::flushLocaleCache($translation->locale));
        static::deleted(fn (self $translation) => self::flushLocaleCache($translation->locale));
    }

    public static function flushLocaleCache(string $locale): void
    {
        Cache::forget("ui-translations:{$locale}:v1");
    }
}
