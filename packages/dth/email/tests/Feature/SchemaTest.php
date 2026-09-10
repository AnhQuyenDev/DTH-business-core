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

        $campaignIndexes = collect(Schema::getIndexes('email_campaigns'))->pluck('name')->all();
        $recipientIndexes = collect(Schema::getIndexes('email_campaign_recipients'))->pluck('name')->all();
        $messageIndexes = collect(Schema::getIndexes('email_messages'))->pluck('name')->all();
        $eventIndexes = collect(Schema::getIndexes('email_events'))->pluck('name')->all();

        $this->assertContains('email_campaigns_started_status_idx', $campaignIndexes);
        $this->assertContains('email_recipients_campaign_status_idx', $recipientIndexes);
        $this->assertContains('email_messages_account_sent_idx', $messageIndexes);
        $this->assertContains('email_events_type_occurred_idx', $eventIndexes);
    }
}
