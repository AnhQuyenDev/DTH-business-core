<?php

namespace Tests\Feature\Ui;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UiUxV4RegressionTest extends TestCase
{
    #[Test]
    public function shared_ui_loads_the_v4_browser_uat_hardening_layer(): void
    {
        $sharedUi = File::get(resource_path('views/filament/ui-system.blade.php'));

        $this->assertStringContainsString("@include('filament.ui-system-v3')", $sharedUi);
        $this->assertStringContainsString("@include('filament.ui-system-v4-fixes')", $sharedUi);
    }

    #[Test]
    public function state_toggle_enhancement_deduplicates_native_state_labels(): void
    {
        $v3 = File::get(resource_path('views/filament/ui-system-v3.blade.php'));
        $v4 = File::get(resource_path('views/filament/ui-system-v4-fixes.blade.php'));

        $this->assertStringContainsString('nativeIsStateLabel', $v3);
        $this->assertStringContainsString('data-dth-generated="true"', $v3);
        $this->assertStringContainsString('dth-state-toggle__native-label--sr-only', $v4);
    }

    #[Test]
    public function v4_hardens_modal_dropdown_input_table_and_action_bar_contrast(): void
    {
        $source = File::get(resource_path('views/filament/ui-system-v4-fixes.blade.php'));

        foreach ([
            'body.fi-body .fi-dropdown-panel',
            'body.fi-body .fi-modal-window',
            'body.fi-body .fi-input-wrp',
            'fi-ta-table > tbody > .fi-ta-row:nth-child(even)',
            'body.fi-body .dth-page-form-actions',
            'position: static !important',
        ] as $marker) {
            $this->assertStringContainsString($marker, $source);
        }
    }

    #[Test]
    public function sidebar_scroll_is_persisted_and_connector_rails_are_replaced(): void
    {
        $source = File::get(resource_path('views/filament/ui-system-v4-fixes.blade.php'));

        $this->assertStringContainsString('SIDEBAR_SCROLL_KEY', $source);
        $this->assertStringContainsString('sessionStorage', $source);
        $this->assertStringContainsString('.fi-sidebar-item-grouped-border', $source);
        $this->assertStringContainsString('.fi-sidebar-sub-group-items', $source);
    }

    #[Test]
    public function every_import_html_header_action_has_the_visible_secondary_button_class(): void
    {
        foreach ([
            app_path('Filament/Resources/EmailTemplateResource/Pages/ListEmailTemplates.php'),
            app_path('Filament/Resources/FormTemplateResource/Pages/ListFormTemplates.php'),
            app_path('Filament/Resources/LandingPageResource/Pages/ListLandingPages.php'),
        ] as $path) {
            $this->assertStringContainsString(
                "->extraAttributes(['class' => 'dth-import-html-action'])",
                File::get($path),
            );
        }
    }
}
