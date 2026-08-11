<?php

namespace Tests\Feature\Ui;

use App\Models\Crm\Department;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DepartmentUiHardeningTest extends TestCase
{
    public function test_department_palette_has_broad_semantic_color_choices(): void
    {
        $options = Department::colorOptions();

        $this->assertGreaterThanOrEqual(19, count($options));
        foreach (['gray', 'primary', 'success', 'warning', 'danger', 'orange', 'emerald', 'teal', 'cyan', 'blue', 'indigo', 'violet', 'purple', 'pink', 'rose'] as $color) {
            $this->assertArrayHasKey($color, $options);
        }
    }

    public function test_staff_table_has_only_one_edit_path_and_no_empty_edit_modal(): void
    {
        $source = File::get(app_path('Livewire/DepartmentStaffTable.php'));

        $this->assertSame(1, substr_count($source, "Action::make('edit')"));
        $this->assertSame(0, substr_count($source, 'EditAction::make'));
        $this->assertStringContainsString("StaffResource::getUrl('edit'", $source);
    }

    public function test_staff_status_is_formatted_through_enum_label(): void
    {
        $source = File::get(app_path('Livewire/DepartmentStaffTable.php'));

        $this->assertStringContainsString('formatStateUsing', $source);
        $this->assertStringContainsString('$state->label()', $source);
    }

    public function test_position_modals_use_translated_explicit_headings(): void
    {
        $source = File::get(app_path('Livewire/DepartmentPositionsTable.php'));

        $this->assertStringContainsString("__('action.create_position')", $source);
        $this->assertStringContainsString("__('action.edit_position')", $source);
        $this->assertStringContainsString("__('action.delete_position')", $source);
    }

    public function test_locale_switch_reloads_the_whole_document(): void
    {
        $source = File::get(app_path('Providers/Filament/AdminPanelProvider.php'));

        $this->assertStringContainsString('window.location.reload()', $source);
        $this->assertStringContainsString("fetch('/language/switch'", $source);
    }
}
