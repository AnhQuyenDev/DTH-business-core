<?php

namespace Tests\Feature\Ui;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UiTranslationCoverageTest extends TestCase
{
    #[Test]
    public function filament_ui_literal_translation_keys_exist_in_vietnamese_and_english(): void
    {
        $files = collect([
            ...File::allFiles(app_path('Filament')),
            ...File::allFiles(app_path('Livewire')),
            ...File::allFiles(app_path('Enums')),
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

    #[Test]
    public function framework_validation_messages_are_localized_for_supported_locales(): void
    {
        foreach (['vi', 'en'] as $locale) {
            app()->setLocale($locale);

            foreach (['required', 'unique', 'email', 'max.string'] as $key) {
                $translated = __("validation.{$key}", [
                    'attribute' => $locale === 'vi' ? 'Tên' : 'Name',
                    'max' => 255,
                ]);

                $this->assertNotSame(
                    "validation.{$key}",
                    $translated,
                    "Missing [{$locale}] validation translation: validation.{$key}",
                );
            }
        }

        app()->setLocale('vi');
        $this->assertStringNotContainsString(
            'The ',
            __('validation.unique', ['attribute' => 'Mã phòng ban']),
        );
    }

    #[Test]
    public function business_enum_labels_follow_the_active_locale(): void
    {
        app()->setLocale('vi');
        $this->assertSame('Đang xử lý', \App\Enums\Crm\LeadIntakeStatus::Active->label());
        $this->assertSame('Người quyết định', \App\Enums\Crm\CompanyContactDecisionRole::DecisionMaker->label());
        $this->assertSame('Chờ đối soát', \App\Enums\Sales\PaymentNoticeStatus::Pending->label());

        app()->setLocale('en');
        $this->assertSame('In Progress', \App\Enums\Crm\LeadIntakeStatus::Active->label());
        $this->assertSame('Decision Maker', \App\Enums\Crm\CompanyContactDecisionRole::DecisionMaker->label());
        $this->assertSame('Pending Reconciliation', \App\Enums\Sales\PaymentNoticeStatus::Pending->label());
    }

    #[Test]
    public function every_english_json_key_has_a_vietnamese_translation_and_vice_versa(): void
    {
        $english = json_decode(File::get(lang_path('en.json')), true, flags: JSON_THROW_ON_ERROR);
        $vietnamese = json_decode(File::get(lang_path('vi.json')), true, flags: JSON_THROW_ON_ERROR);

        foreach (array_keys($english) as $key) {
            $this->assertArrayHasKey($key, $vietnamese, "Missing [vi] JSON translation: {$key}");
        }

        foreach (array_keys($vietnamese) as $key) {
            $this->assertArrayHasKey($key, $english, "Missing [en] JSON translation: {$key}");
        }
    }

    #[Test]
    public function locale_validation_files_exist_for_every_supported_locale(): void
    {
        foreach (['vi', 'en'] as $locale) {
            $this->assertFileExists(lang_path("{$locale}/validation.php"));
            $messages = require lang_path("{$locale}/validation.php");
            $this->assertIsArray($messages);
            $this->assertArrayHasKey('required', $messages);
            $this->assertArrayHasKey('unique', $messages);
        }
    }
}
