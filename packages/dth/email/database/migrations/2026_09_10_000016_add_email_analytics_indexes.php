<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('email_campaigns', function (Blueprint $table): void {
            $table->index(['started_at', 'status'], 'email_campaigns_started_status_idx');
            $table->index(['sending_account_id', 'started_at'], 'email_campaigns_account_started_idx');
        });

        Schema::table('email_campaign_recipients', function (Blueprint $table): void {
            $table->index(['campaign_id', 'status'], 'email_recipients_campaign_status_idx');
            $table->index(['campaign_id', 'sent_at'], 'email_recipients_campaign_sent_idx');
            $table->index(['campaign_id', 'opened_at'], 'email_recipients_campaign_opened_idx');
            $table->index(['campaign_id', 'clicked_at'], 'email_recipients_campaign_clicked_idx');
        });

        Schema::table('email_messages', function (Blueprint $table): void {
            $table->index(['sending_account_id', 'sent_at'], 'email_messages_account_sent_idx');
            $table->index(['status', 'sent_at'], 'email_messages_status_sent_idx');
            $table->index(['status', 'failed_at'], 'email_messages_status_failed_idx');
        });

        Schema::table('email_events', function (Blueprint $table): void {
            $table->index(['event_type', 'occurred_at'], 'email_events_type_occurred_idx');
        });

        Schema::table('email_tracked_links', function (Blueprint $table): void {
            $table->index(['message_id', 'last_clicked_at'], 'email_links_message_clicked_idx');
        });

        Schema::table('email_suppressions', function (Blueprint $table): void {
            $table->index(['reason', 'released_at'], 'email_suppressions_reason_released_idx');
        });
    }

    public function down(): void
    {
        Schema::table('email_suppressions', function (Blueprint $table): void {
            $table->dropIndex('email_suppressions_reason_released_idx');
        });

        Schema::table('email_tracked_links', function (Blueprint $table): void {
            $table->dropIndex('email_links_message_clicked_idx');
        });

        Schema::table('email_events', function (Blueprint $table): void {
            $table->dropIndex('email_events_type_occurred_idx');
        });

        Schema::table('email_messages', function (Blueprint $table): void {
            $table->dropIndex('email_messages_account_sent_idx');
            $table->dropIndex('email_messages_status_sent_idx');
            $table->dropIndex('email_messages_status_failed_idx');
        });

        Schema::table('email_campaign_recipients', function (Blueprint $table): void {
            $table->dropIndex('email_recipients_campaign_status_idx');
            $table->dropIndex('email_recipients_campaign_sent_idx');
            $table->dropIndex('email_recipients_campaign_opened_idx');
            $table->dropIndex('email_recipients_campaign_clicked_idx');
        });

        Schema::table('email_campaigns', function (Blueprint $table): void {
            $table->dropIndex('email_campaigns_started_status_idx');
            $table->dropIndex('email_campaigns_account_started_idx');
        });
    }
};
