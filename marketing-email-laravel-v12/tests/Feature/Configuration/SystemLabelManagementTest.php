<?php

namespace Tests\Feature\Configuration;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\Crm\PositionAuthority;
use App\Enums\Crm\PositionGroup;
use App\Enums\Marketing\LandingPageStatus;
use App\Enums\Sales\AudienceType;
use App\Enums\UserRole;
use App\Models\Crm\Department;
use App\Models\Marketing\AuditLog;
use App\Models\System\UiBadgeStyle;
use App\Services\System\SystemLabelColorService;
use App\Support\Ui\BadgePalette;
use App\Support\Ui\Labels\SystemLabelCatalog;
use App\Support\Ui\Labels\SystemLabelRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SystemLabelManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_contains_dynamic_and_system_managed_labels(): void
    {
        $department = Department::factory()->create([
            'name' => 'Tài chính nội bộ',
            'color' => 'green',
            'default_color' => 'green',
        ]);

        $registry = app(SystemLabelRegistry::class);
        $identifiers = $registry->all()->pluck('identifier');

        $this->assertTrue($identifiers->contains('organization.department.'.$department->id));
        $this->assertTrue($identifiers->contains('organization.position_group.professional'));
        $this->assertTrue($identifiers->contains('organization.position_authority.manager'));
        $this->assertTrue($identifiers->contains('system.access_role.super_admin'));
        $this->assertTrue($identifiers->contains('marketing.landing_page_status.published'));
        $this->assertTrue($identifiers->contains('finance.payment_record_status.verified'));
        $this->assertTrue($identifiers->contains('marketing.email_template_status.active'));
        $this->assertFalse($identifiers->contains('system.access_role.customer_service_manager'));
    }

    public function test_every_colored_enum_is_registered_or_intentionally_shared(): void
    {
        $sharedEnums = [
            UserRole::class,
            DepartmentFunction::class,
            PositionGroup::class,
            PositionAuthority::class,
            AudienceType::class,
        ];

        foreach (File::allFiles(app_path('Enums')) as $file) {
            if (! Str::contains($file->getContents(), 'function color(')) {
                continue;
            }

            $relative = Str::after($file->getPathname(), app_path().DIRECTORY_SEPARATOR);
            $class = 'App\\'.Str::of($relative)
                ->replace(DIRECTORY_SEPARATOR, '\\')
                ->replaceLast('.php', '')
                ->toString();

            $this->assertTrue(
                in_array($class, $sharedEnums, true)
                    || SystemLabelCatalog::metadataForEnum($class) !== null,
                "Colored enum [{$class}] must be registered in SystemLabelCatalog.",
            );
        }
    }

    public function test_registry_batches_usage_counts_and_reuses_the_short_lived_snapshot(): void
    {
        Cache::flush();
        DB::enableQueryLog();

        $labels = app(SystemLabelRegistry::class)->all();
        $firstQueryCount = count(DB::getQueryLog());

        DB::flushQueryLog();
        $cachedLabels = app(SystemLabelRegistry::class)->all();
        $cachedQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertNotEmpty($labels);
        $this->assertSame($labels->count(), $cachedLabels->count());
        $this->assertGreaterThan(0, $firstQueryCount);
        $this->assertLessThan($labels->count(), $firstQueryCount);
        $this->assertSame(0, $cachedQueryCount);
    }

    public function test_directory_waits_for_a_filter_before_rendering_the_result_table(): void
    {
        $page = File::get(app_path('Filament/Pages/SystemLabelManagementPage.php'));
        $view = File::get(resource_path('views/filament/pages/system-label-management-page.blade.php'));

        $this->assertStringContainsString('$showResults = $this->hasActiveFilters()', $page);
        $this->assertStringContainsString('@if ($showResults)', $view);
        $this->assertStringContainsString('wire:model.live.debounce.500ms="search"', $view);
        $this->assertStringContainsString('appearance: none !important', $view);
    }

    public function test_enum_status_override_is_isolated_by_category_and_resettable(): void
    {
        $this->assertSame('success', BadgePalette::withoutOverrides(
            fn (): string => LandingPageStatus::Published->color(),
        ));

        UiBadgeStyle::query()->create([
            'category' => 'marketing.landing_page_status',
            'key' => LandingPageStatus::Published->value,
            'color' => 'purple',
        ]);

        $this->assertSame('purple', LandingPageStatus::Published->color());
        $this->assertSame('success', BadgePalette::status('published'));
    }

    public function test_critical_label_requires_acknowledgement_and_reason(): void
    {
        $definition = app(SystemLabelRegistry::class)
            ->find('marketing.landing_page_status.published');
        $service = app(SystemLabelColorService::class);

        $this->assertNotNull($definition);

        try {
            $service->change($definition, 'purple', false, null);
            $this->fail('The guarded service accepted a critical change without acknowledgement.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('acknowledged', $exception->errors());
        }

        try {
            $service->change($definition, 'purple', true, null);
            $this->fail('The guarded service accepted a critical change without a reason.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('changeReason', $exception->errors());
        }

        $this->assertTrue($service->change(
            $definition,
            'purple',
            true,
            'Đổi màu theo quy ước nhận diện đã được duyệt.',
        ));

        $this->assertDatabaseHas('ui_badge_styles', [
            'category' => 'marketing.landing_page_status',
            'key' => 'published',
            'color' => 'purple',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'system_label.color_changed',
        ]);
    }

    public function test_department_color_uses_same_record_and_can_restore_default(): void
    {
        $department = Department::factory()->create([
            'color' => 'green',
            'default_color' => 'green',
        ]);

        $registry = app(SystemLabelRegistry::class);
        $service = app(SystemLabelColorService::class);
        $definition = $registry->find('organization.department.'.$department->id);

        $this->assertNotNull($definition);
        $this->assertTrue($service->change($definition, 'indigo', true));
        $this->assertSame('indigo', $department->fresh()->color);

        $changedDefinition = $registry->find('organization.department.'.$department->id);
        $this->assertNotNull($changedDefinition);
        $this->assertTrue($changedDefinition->isCustomized());
        $this->assertTrue($service->reset($changedDefinition, true));
        $this->assertSame('green', $department->fresh()->color);
        $this->assertSame(2, AuditLog::query()->whereIn('action', [
            'system_label.color_changed',
            'system_label.color_reset',
        ])->count());
    }
}
