<?php

namespace Tests\Feature\Ui;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class UiTranslationCoverageTest extends TestCase
{
    /** @test */
    public function filament_ui_literal_translation_keys_exist_in_vietnamese_and_english(): void
    {
        $files = collect([
            ...File::allFiles(app_path('Filament')),
            ...File::allFiles(resource_path('views/filament')),
        ]);

        $keys = $files
            ->flatMap(function ($file): array {
                preg_match_all(
                    "/(?:__|trans)\\(\\s*['\"]([^'\"]+)['\"]/",
                    File::get($file->getPathname()),
                    $matches,
                );

                return $matches[1] ?? [];
            })
            ->filter(fn (string $key): bool => ! str_contains($key, '$'))
            ->filter(fn (string $key): bool => ! str_contains($key, '{'))
            ->filter(fn (string $key): bool => ! str_ends_with($key, '.'))
            ->filter(fn (string $key): bool => ! str_ends_with($key, '_'))
            ->filter(fn (string $key): bool => ! str_starts_with($key, 'filament-'))
            ->unique()
            ->values();

        foreach (['vi', 'en'] as $locale) {
            app()->setLocale($locale);

            foreach ($keys as $key) {
                $translated = __($key);

                $this->assertNotSame(
                    $key,
                    $translated,
                    "Missing [{$locale}] UI translation: {$key}",
                );
                $this->assertIsString(
                    $translated,
                    "UI translation [{$locale}] {$key} must resolve to text, not a nested array.",
                );
            }
        }
    }
}
