<?php

namespace Tests\Feature;

use App\Models\SystemTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vietnamese_catalog_translation_is_resolved(): void
    {
        app()->setLocale('vi');

        $this->assertSame('Chiến dịch', ui_t('email.navigation.campaigns', 'Campaigns'));
        $this->assertSame('Hoàn tất', ui_status('completed'));
    }

    public function test_locale_switch_is_persisted_to_session_and_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from('/admin')
            ->post(route('locale.switch', ['locale' => 'vi']));

        $response->assertRedirect('/admin');
        $response->assertSessionHas('locale', 'vi');

        $this->assertSame('vi', $user->fresh()->preferred_locale);
    }

    public function test_unknown_vietnamese_key_is_registered_for_later_translation(): void
    {
        app()->setLocale('vi');

        $this->assertSame(
            'Future module label',
            ui_t('future.module.label', 'Future module label', module: 'future')
        );

        $this->assertDatabaseHas('system_translations', [
            'translation_key' => 'future.module.label',
            'locale' => 'vi',
            'default_value' => 'Future module label',
            'module' => 'future',
            'source' => 'missing',
            'is_reviewed' => false,
        ]);
    }

    public function test_database_override_has_priority_over_root_catalog(): void
    {
        SystemTranslation::query()->create([
            'translation_key' => 'email.navigation.campaigns',
            'locale' => 'vi',
            'value' => 'Chiến dịch Email tùy chỉnh',
            'default_value' => 'Campaigns',
            'module' => 'email',
            'source' => 'manual',
            'is_reviewed' => true,
        ]);

        app()->setLocale('vi');

        $this->assertSame(
            'Chiến dịch Email tùy chỉnh',
            ui_t('email.navigation.campaigns', 'Campaigns')
        );
    }
}
