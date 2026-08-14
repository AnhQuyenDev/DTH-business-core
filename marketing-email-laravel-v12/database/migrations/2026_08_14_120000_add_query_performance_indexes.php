<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            $table->index(['assigned_staff_id', 'created_at'], 'quotations_staff_created_at_idx');
            $table->index(['assigned_staff_id', 'accepted_at'], 'quotations_staff_accepted_at_idx');
            $table->index('sent_at', 'quotations_sent_at_idx');
            $table->index('first_viewed_at', 'quotations_first_viewed_at_idx');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->index(['verified_by_user_id', 'verified_at'], 'payments_verifier_verified_at_idx');
        });

        Schema::table('customer_interactions', function (Blueprint $table): void {
            $table->index(['staff_id', 'interaction_at'], 'customer_interactions_staff_at_idx');
        });

        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->index(['assigned_staff_id', 'last_activity_at'], 'support_tickets_staff_activity_idx');
            $table->index(['assigned_staff_id', 'resolved_at'], 'support_tickets_staff_resolved_idx');
        });

        Schema::table('marketing_campaigns', function (Blueprint $table): void {
            $table->index(['created_by', 'created_at'], 'marketing_campaigns_creator_created_idx');
        });

        Schema::table('campaigns', function (Blueprint $table): void {
            $table->index(['created_by', 'created_at'], 'campaigns_creator_created_idx');
        });

        Schema::table('landing_pages', function (Blueprint $table): void {
            $table->index(['created_by', 'created_at'], 'landing_pages_creator_created_idx');
        });

        Schema::table('landing_page_views', function (Blueprint $table): void {
            $table->index(['landing_page_id', 'viewed_at'], 'landing_page_views_page_viewed_idx');
        });

        Schema::table('landing_page_submissions', function (Blueprint $table): void {
            $table->index(['landing_page_id', 'submitted_at'], 'lp_submissions_page_submitted_idx');
            $table->index(['marketing_campaign_id', 'submitted_at'], 'lp_submissions_campaign_submitted_idx');
        });

        Schema::table('leads', function (Blueprint $table): void {
            $table->index('created_at', 'leads_created_at_idx');
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->index('next_follow_up_at', 'customers_next_follow_up_at_idx');
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->index(['user_id', 'created_at'], 'audit_logs_user_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', fn (Blueprint $table) => $table->dropIndex('audit_logs_user_created_at_idx'));
        Schema::table('customers', fn (Blueprint $table) => $table->dropIndex('customers_next_follow_up_at_idx'));
        Schema::table('leads', fn (Blueprint $table) => $table->dropIndex('leads_created_at_idx'));
        Schema::table('landing_page_submissions', function (Blueprint $table): void {
            $table->dropIndex('lp_submissions_page_submitted_idx');
            $table->dropIndex('lp_submissions_campaign_submitted_idx');
        });
        Schema::table('landing_page_views', fn (Blueprint $table) => $table->dropIndex('landing_page_views_page_viewed_idx'));
        Schema::table('landing_pages', fn (Blueprint $table) => $table->dropIndex('landing_pages_creator_created_idx'));
        Schema::table('campaigns', fn (Blueprint $table) => $table->dropIndex('campaigns_creator_created_idx'));
        Schema::table('marketing_campaigns', fn (Blueprint $table) => $table->dropIndex('marketing_campaigns_creator_created_idx'));
        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->dropIndex('support_tickets_staff_activity_idx');
            $table->dropIndex('support_tickets_staff_resolved_idx');
        });
        Schema::table('customer_interactions', fn (Blueprint $table) => $table->dropIndex('customer_interactions_staff_at_idx'));
        Schema::table('payments', fn (Blueprint $table) => $table->dropIndex('payments_verifier_verified_at_idx'));
        Schema::table('quotations', function (Blueprint $table): void {
            $table->dropIndex('quotations_staff_created_at_idx');
            $table->dropIndex('quotations_staff_accepted_at_idx');
            $table->dropIndex('quotations_sent_at_idx');
            $table->dropIndex('quotations_first_viewed_at_idx');
        });
    }
};
