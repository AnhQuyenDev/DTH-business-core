<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_landing_page_views', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('landing_page_id')
                ->constrained('marketing_landing_pages')
                ->cascadeOnDelete();
            $table->foreignId('marketing_campaign_id')
                ->nullable()
                ->constrained('marketing_campaigns')
                ->nullOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('referrer')->nullable();
            $table->string('utm_source')->nullable()->index();
            $table->string('utm_medium')->nullable()->index();
            $table->string('utm_campaign')->nullable()->index();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();
            $table->timestamp('viewed_at')->index();
            $table->timestamps();

            $table->index(
                ['landing_page_id', 'viewed_at'],
                'marketing_lp_views_page_viewed_idx',
            );
            $table->index(
                ['marketing_campaign_id', 'viewed_at'],
                'marketing_lp_views_campaign_viewed_idx',
            );
        });

        Schema::create('marketing_landing_page_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('landing_page_id')
                ->constrained('marketing_landing_pages')
                ->cascadeOnDelete();
            $table->foreignId('marketing_campaign_id')
                ->nullable()
                ->constrained('marketing_campaigns')
                ->nullOnDelete();
            $table->foreignId('form_template_id')
                ->nullable()
                ->constrained('marketing_form_templates')
                ->nullOnDelete();

            $table->uuid('submission_token')->nullable();
            $table->char('payload_fingerprint', 64)->index();
            $table->char('member_key', 64)->index();
            $table->string('submission_type', 20)->index();
            $table->json('data');
            $table->string('normalized_email')->nullable()->index();
            $table->string('normalized_phone', 50)->nullable()->index();
            $table->string('display_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('service_reference')->nullable()->index();
            $table->string('package_reference')->nullable()->index();

            // Cross-module references are strings rather than foreign keys.
            // CRM remains the owner of Contact/Lead records.
            $table->string('contact_reference')->nullable()->index();
            $table->string('lead_reference')->nullable()->index();
            $table->string('lead_code')->nullable();
            $table->string('lead_status')->nullable()->index();

            $table->string('status', 32)->default('received')->index();
            $table->string('contact_action', 32)->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('tags')->nullable();
            $table->json('integration_snapshot')->nullable();

            $table->string('source')->nullable()->index();
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('referrer')->nullable();
            $table->string('utm_source')->nullable()->index();
            $table->string('utm_medium')->nullable()->index();
            $table->string('utm_campaign')->nullable()->index();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();
            $table->timestamp('submitted_at')->index();
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamps();

            $table->unique(
                ['landing_page_id', 'submission_token'],
                'marketing_lp_submissions_page_token_unique',
            );
            $table->index(
                ['landing_page_id', 'submitted_at'],
                'marketing_lp_submissions_page_submitted_idx',
            );
            $table->index(
                ['marketing_campaign_id', 'submitted_at'],
                'marketing_lp_submissions_campaign_submitted_idx',
            );
            $table->index(
                ['status', 'submitted_at'],
                'marketing_lp_submissions_status_submitted_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_landing_page_submissions');
        Schema::dropIfExists('marketing_landing_page_views');
    }
};
