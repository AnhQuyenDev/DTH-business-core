<?php

namespace App\Support\Localization;

use App\Models\SystemTranslation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class UiTranslator
{
    private ?bool $translationTableExists = null;

    /**
     * Translate a stable UI key while preserving a canonical default string.
     * DB values override root config values. Missing non-default translations
     * are registered once for later manual/AI completion.
     */
    public function translate(
        string $key,
        ?string $default = null,
        array $replace = [],
        ?string $module = null,
        ?string $context = null,
    ): string {
        $locale = app()->getLocale();
        $default ??= $this->humanizeKey($key);

        $value = $this->databaseTranslations($locale)[$key]
            ?? data_get(config('localization.translations', []), "{$locale}.{$key}");

        if (! is_string($value) || $value === '') {
            $value = $default;

            if ($locale !== config('localization.fallback', 'en')) {
                $this->registerMissing($key, $locale, $default, $module, $context);
            }
        }

        return $this->replace($value, $replace);
    }

    public function status(mixed $state): string
    {
        $value = $state instanceof \BackedEnum
            ? $state->value
            : (string) $state;

        $default = ucfirst(str_replace(['_', '-'], ' ', $value));

        return $this->translate(
            key: 'common.statuses.'.$value,
            default: $default,
            module: 'core',
            context: 'status',
        );
    }

    /** @return array<string, string> */
    private function databaseTranslations(string $locale): array
    {
        if (! $this->hasTranslationTable()) {
            return [];
        }

        try {
            return Cache::remember(
                "ui-translations:{$locale}:v1",
                now()->addHour(),
                fn (): array => SystemTranslation::query()
                    ->where('locale', $locale)
                    ->whereNotNull('value')
                    ->where('value', '!=', '')
                    ->pluck('value', 'translation_key')
                    ->all(),
            );
        } catch (Throwable) {
            return [];
        }
    }

    private function registerMissing(
        string $key,
        string $locale,
        string $default,
        ?string $module,
        ?string $context,
    ): void {
        if (! config('localization.capture_missing', true) || ! $this->hasTranslationTable()) {
            return;
        }

        $seenKey = 'ui-translation-missing-seen:'.sha1($locale.'|'.$key);

        try {
            if (! Cache::add($seenKey, true, now()->addDay())) {
                return;
            }

            SystemTranslation::query()->updateOrCreate(
                [
                    'translation_key' => $key,
                    'locale' => $locale,
                ],
                [
                    'default_value' => $default,
                    'module' => $module,
                    'context' => $context,
                    'source' => 'missing',
                    'is_reviewed' => false,
                    'last_seen_at' => now(),
                ],
            );
        } catch (Throwable) {
            // Translation must never make the application unusable. If the DB
            // is unavailable, fall back to the canonical text for this request.
        }
    }

    private function hasTranslationTable(): bool
    {
        if ($this->translationTableExists !== null) {
            return $this->translationTableExists;
        }

        try {
            return $this->translationTableExists = Schema::hasTable('system_translations');
        } catch (Throwable) {
            return $this->translationTableExists = false;
        }
    }

    private function replace(string $value, array $replace): string
    {
        if ($replace === []) {
            return $value;
        }

        $pairs = [];

        foreach ($replace as $key => $replacement) {
            $pairs[':'.$key] = (string) $replacement;
        }

        return strtr($value, $pairs);
    }

    private function humanizeKey(string $key): string
    {
        $segment = str($key)->afterLast('.')->replace(['_', '-'], ' ')->toString();

        return str($segment)->headline()->toString();
    }
}
