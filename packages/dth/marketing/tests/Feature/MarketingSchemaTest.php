<?php

namespace Dth\Marketing\Tests\Feature;

use Dth\Marketing\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

class MarketingSchemaTest extends TestCase
{
    public function test_mb_through_md_tables_and_columns_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('marketing_campaigns', [
            'status_changed_at',
            'service_references',
            'service_snapshot',
        ]));

        $this->assertTrue(Schema::hasTable('marketing_form_templates'));
        $this->assertTrue(Schema::hasColumns('marketing_form_templates', [
            'audience_type',
            'status',
            'version',
            'html_body',
        ]));

        $this->assertTrue(Schema::hasTable('marketing_form_fields'));
        $this->assertTrue(Schema::hasColumns('marketing_form_fields', [
            'field_key',
            'field_type',
            'contact_mapping',
            'semantic_role',
            'semantic_confidence',
            'semantic_source',
            'validation_rules',
            'sort_order',
        ]));

        $this->assertTrue(Schema::hasTable('marketing_landing_pages'));
        $this->assertTrue(Schema::hasColumns('marketing_landing_pages', [
            'marketing_campaign_id',
            'personal_form_template_id',
            'business_form_template_id',
            'html_body',
            'css_body',
            'theme_tokens',
            'service_reference',
            'package_references',
            'status',
            'published_at',
        ]));

        $this->assertTrue(Schema::hasColumns('marketing_landing_page_submissions', [
            'data',
            'normalized_data',
            'display_name',
            'normalized_email',
            'normalized_phone',
        ]));
    }
}
