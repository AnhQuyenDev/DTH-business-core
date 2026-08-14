<?php

namespace Tests\Feature\Configuration;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\Crm\PositionAuthority;
use App\Enums\Crm\PositionGroup;
use App\Filament\Pages\AccessControlPage;
use App\Filament\Pages\AppearanceSettingsPage;
use App\Filament\Pages\OrganizationAccessPage;
use App\Filament\Pages\SystemLabelManagementPage;
use App\Filament\Resources\DepartmentResource;
use App\Filament\Resources\PositionResource;
use App\Filament\Resources\StaffResource;
use App\Models\Crm\Department;
use App\Models\Crm\Position;
use App\Models\Crm\Staff;
use App\Models\Crm\StaffBusinessFunction;
use App\Models\System\UiBadgeStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesV1Actors;
use Tests\TestCase;

class V1OrganizationUiActionsTest extends TestCase
{
    use MakesV1Actors;
    use RefreshDatabase;

    public function test_system_admin_can_open_the_three_configuration_hubs_and_organization_catalogs(): void
    {
        $this->actingAs($this->makeV1SystemAdmin());

        $this->get(OrganizationAccessPage::getUrl())->assertOk();
        $this->get(AccessControlPage::getUrl())->assertOk();
        $this->get(AppearanceSettingsPage::getUrl())
            ->assertRedirect(SystemLabelManagementPage::getUrl());
        $this->get(DepartmentResource::getUrl())->assertOk();
        $this->get(PositionResource::getUrl())->assertOk();
        $this->get(StaffResource::getUrl())->assertOk();
    }

    public function test_business_staff_cannot_open_system_configuration_surfaces(): void
    {
        foreach ([
            $this->makeV1MarketingManager()[0],
            $this->makeV1SalesManager()[0],
            $this->makeV1CustomerCareManager()[0],
            $this->makeV1Finance()[0],
        ] as $actor) {
            $this->actingAs($actor);
            session()->flush();
            $this->get(DepartmentResource::getUrl())->assertForbidden();
            $this->get(PositionResource::getUrl())->assertForbidden();
            $this->get(StaffResource::getUrl())->assertForbidden();
        }
    }

    public function test_one_global_job_title_can_be_reused_by_staff_in_multiple_departments(): void
    {
        $sales = Department::factory()->create(['code' => 'sales-v2', 'name' => 'Phòng Kinh doanh']);
        $care = Department::factory()->create(['code' => 'care-v2', 'name' => 'Phòng CSKH']);
        $title = Position::factory()->create([
            'code' => 'chuyen-vien',
            'title' => 'Chuyên viên',
            'group_key' => PositionGroup::Professional->value,
            'authority_level' => PositionAuthority::Member->value,
            'department_id' => null,
        ]);

        $first = Staff::factory()->create(['department_id' => $sales->id, 'position_id' => $title->id]);
        $second = Staff::factory()->create(['department_id' => $care->id, 'position_id' => $title->id]);

        $this->assertSame($title->id, $first->position_id);
        $this->assertSame($title->id, $second->position_id);
        $this->assertSame(1, Position::query()->where('title', 'Chuyên viên')->count());
    }

    public function test_department_identity_color_does_not_overwrite_business_function_color(): void
    {
        UiBadgeStyle::query()->create([
            'category' => 'department_function',
            'key' => DepartmentFunction::Finance->value,
            'color' => 'blue',
        ]);

        $department = Department::factory()->create([
            'function_key' => DepartmentFunction::Finance->value, // legacy compatibility only
            'color' => 'rose',
        ]);
        $department->update(['color' => 'indigo']);

        $this->assertDatabaseHas('ui_badge_styles', [
            'category' => 'department_function',
            'key' => DepartmentFunction::Finance->value,
            'color' => 'blue',
        ]);
        $this->assertSame('indigo', $department->fresh()->color);
    }

    public function test_business_function_is_assigned_to_staff_independently_from_physical_department(): void
    {
        $department = Department::factory()->create([
            'code' => 'growth-team-v2',
            'name' => 'Phòng Growth',
            'function_key' => DepartmentFunction::Marketing->value,
        ]);
        $staff = Staff::factory()->create(['department_id' => $department->id]);

        StaffBusinessFunction::query()->create([
            'staff_id' => $staff->id,
            'function_key' => DepartmentFunction::Finance->value,
            'authority_level' => PositionAuthority::Member->value,
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->assertTrue($staff->fresh()->hasBusinessFunction(DepartmentFunction::Finance));
        $this->assertSame(DepartmentFunction::Finance, $staff->fresh()->primaryBusinessFunction());
    }
}
