<?php

namespace Tests\Feature\Configuration;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\Crm\PositionAuthority;
use App\Enums\Crm\PositionGroup;
use App\Models\Crm\Department;
use App\Models\Crm\Position;
use App\Models\Crm\Staff;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ConfigurationV2ArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_title_title_is_globally_unique(): void
    {
        Position::factory()->create(['code' => 'staff', 'title' => 'Chuyên viên']);

        $this->expectException(QueryException::class);
        Position::factory()->create(['code' => 'staff-duplicate', 'title' => 'Chuyên viên']);
    }

    public function test_generic_job_title_supports_any_business_function(): void
    {
        $position = Position::factory()->create([
            'function_key' => null,
            'title' => 'Phó phòng',
            'code' => 'pho-phong-any',
        ]);

        $this->assertTrue($position->supportsBusinessFunction(DepartmentFunction::Sales));
        $this->assertTrue($position->supportsBusinessFunction(DepartmentFunction::Finance));
    }

    public function test_specialist_job_title_can_express_business_scope_without_owning_a_department(): void
    {
        $position = Position::factory()->create([
            'title' => 'Kế toán trưởng',
            'code' => 'ke-toan-truong',
            'group_key' => PositionGroup::Management->value,
            'authority_level' => PositionAuthority::Manager->value,
            'function_key' => DepartmentFunction::Finance->value,
            'department_id' => null,
        ]);

        $this->assertTrue($position->supportsBusinessFunction(DepartmentFunction::Finance));
        $this->assertFalse($position->supportsBusinessFunction(DepartmentFunction::Marketing));
        $this->assertNull($position->department_id);
    }

    public function test_same_job_title_is_reusable_across_departments(): void
    {
        $title = Position::factory()->create(['code' => 'specialist', 'title' => 'Chuyên viên']);
        $firstDepartment = Department::factory()->create(['code' => 'dept-a']);
        $secondDepartment = Department::factory()->create(['code' => 'dept-b']);

        Staff::factory()->create(['department_id' => $firstDepartment->id, 'position_id' => $title->id]);
        Staff::factory()->create(['department_id' => $secondDepartment->id, 'position_id' => $title->id]);

        $this->assertSame(2, Staff::query()->where('position_id', $title->id)->count());
        $this->assertSame(1, Position::query()->where('title', 'Chuyên viên')->count());
    }

    public function test_position_authority_labels_follow_configuration_locale(): void
    {
        app()->setLocale('vi');
        $this->assertSame('Quản lý', PositionAuthority::Manager->label());
        $this->assertSame('Lãnh đạo cấp cao', PositionAuthority::Executive->label());

        app()->setLocale('en');
        $this->assertSame('Manager', PositionAuthority::Manager->label());
        $this->assertSame('Executive', PositionAuthority::Executive->label());
    }

    public function test_v2_migration_retains_department_column_only_for_compatibility_and_clears_ownership(): void
    {
        $source = File::get(database_path('migrations/2026_08_11_200000_globalize_job_titles_for_configuration_v2.php'));

        $this->assertStringContainsString("DB::table('positions')->update(['department_id' => null])", $source);
        $this->assertStringContainsString('positions_title_global_unique', $source);
        $this->assertStringContainsString('positions_code_global_unique', $source);
    }
}
