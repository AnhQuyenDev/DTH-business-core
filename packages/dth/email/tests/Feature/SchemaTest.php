<?php

namespace Dth\Email\Tests\Feature;

use Dth\Email\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

class SchemaTest extends TestCase
{
    public function test_email_schema_is_installed(): void
    {
        foreach ([
            'email_sending_domains',
            'email_sending_accounts',
            'email_template_categories',
            'email_templates',
            'email_campaigns',
            'email_campaign_recipients',
            'email_messages',
            'email_events',
            'email_tracked_links',
            'email_suppressions',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }

        $this->assertTrue(Schema::hasColumns('email_templates', [
            'template_key',
            'description',
        ]));

        $this->assertFalse(Schema::hasColumn('email_templates', 'sample_variables'));
        $this->assertFalse(Schema::hasColumn('email_template_categories', 'sort_order'));
    }
}
