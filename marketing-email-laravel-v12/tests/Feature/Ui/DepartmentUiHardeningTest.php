<?php

namespace Tests\Feature\Ui;

use App\Models\Crm\Department;
use App\Support\Ui\BadgePalette;
use App\Support\Ui\SystemColorPalette;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DepartmentUiHardeningTest extends TestCase
{
    public function test_configuration_uses_one_broad_shared_color_palette(): void
    {
        $departmentOptions = Department::colorOptions();
        $badgeOptions = BadgePalette::colorOptions();

        $this->assertGreaterThanOrEqual(22, count($departmentOptions));
        $this->assertSame(array_keys($departmentOptions), array_keys($badgeOptions));
        $this->assertSame(SystemColorPalette::keys(), array_keys($departmentOptions));
    }

    public function test_staff_table_has_only_one_edit_path_and_no_empty_edit_modal(): void
    {
        $source = File::get(app_path('Livewire/DepartmentStaffTable.php'));

        $this->assertSame(1, substr_count($source, "Action::make('edit')"));
        $this->assertSame(0, substr_count($source, 'EditAction::make'));
        $this->assertStringContainsString("StaffResource::getUrl('edit'", $source);
    }

    public function test_department_detail_does_not_manage_department_scoped_job_titles_anymore(): void
    {
        $source = File::get(resource_path('views/filament/resources/department-resource/pages/list-departments.blade.php'));

        $this->assertStringNotContainsString('department-positions-table', $source);
        $this->assertStringNotContainsString('global_positions_title', $source);
        $this->assertStringNotContainsString('PositionResource::getUrl()', $source);
        $this->assertFileDoesNotExist(app_path('Livewire/DepartmentPositionsTable.php'));
    }

    public function test_global_job_title_resource_has_no_department_field_and_no_bulk_delete(): void
    {
        $source = File::get(app_path('Filament/Resources/PositionResource.php'));

        $this->assertStringNotContainsString("Select::make('department_id')", $source);
        $this->assertStringContainsString("TextInput::make('title')", $source);
        $this->assertStringContainsString('->unique(ignoreRecord: true)', $source);
        $this->assertStringNotContainsString('DeleteBulkAction::make', $source);
    }

    public function test_navigation_separates_organization_access_and_appearance(): void
    {
        foreach (['DepartmentResource.php', 'PositionResource.php', 'StaffResource.php'] as $resource) {
            $source = File::get(app_path('Filament/Resources/'.$resource));
            $this->assertStringContainsString('OrganizationAccessPage::getNavigationLabel()', $source);
        }

        foreach (['UserResource.php', 'Security/RbacRoleResource.php'] as $resource) {
            $source = File::get(app_path('Filament/Resources/'.$resource));
            $this->assertStringContainsString('AccessControlPage::getNavigationLabel()', $source);
        }

        $this->assertStringContainsString(
            'AppearanceSettingsPage::getNavigationLabel()',
            File::get(app_path('Filament/Resources/UiBadgeStyleResource.php')),
        );
    }

    public function test_business_status_colors_are_semantic_and_not_user_editable(): void
    {
        $this->assertArrayNotHasKey('status', BadgePalette::editableCategoryOptions());
        $this->assertSame('success', BadgePalette::status('paid'));
        $this->assertSame('warning', BadgePalette::status('pending'));
        $this->assertSame('danger', BadgePalette::status('rejected'));

        $source = File::get(app_path('Filament/Resources/UiBadgeStyleResource.php'));
        $this->assertStringContainsString("where('category', '!=', 'status')", $source);
    }

    public function test_locale_switch_reloads_the_whole_document(): void
    {
        $source = File::get(app_path('Providers/Filament/AdminPanelProvider.php'));

        $this->assertStringContainsString('window.location.reload()', $source);
        $this->assertStringContainsString("fetch('/language/switch'", $source);
    }
}
