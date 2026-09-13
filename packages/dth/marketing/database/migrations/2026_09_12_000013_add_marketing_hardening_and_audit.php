<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('action', 120)->index();
            $table->string('auditable_type')->nullable();
            $table->string('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->timestamp('created_at')->index();

            $table->index(
                ['auditable_type', 'auditable_id', 'created_at'],
                'marketing_audit_auditable_created_idx',
            );
            $table->index(
                ['action', 'created_at'],
                'marketing_audit_action_created_idx',
            );
        });

        Schema::table('marketing_landing_page_views', function (Blueprint $table): void {
            // Acquisition source is persisted on views as well as submissions so
            // referral/direct attribution uses the same dimension on both sides
            // of the View -> Submission funnel. Existing M-E/M-G columns remain
            // untouched.
            $table->string('source')->nullable();
            $table->index(['source', 'viewed_at'], 'marketing_views_acquisition_source_viewed_idx');
            $table->index(['utm_source', 'viewed_at'], 'marketing_views_source_viewed_idx');
            $table->index(['utm_medium', 'viewed_at'], 'marketing_views_medium_viewed_idx');
            $table->index(['utm_campaign', 'viewed_at'], 'marketing_views_utm_campaign_viewed_idx');
        });

        Schema::table('marketing_landing_page_submissions', function (Blueprint $table): void {
            $table->index(['utm_source', 'submitted_at'], 'marketing_subs_source_submitted_idx');
            $table->index(['utm_medium', 'submitted_at'], 'marketing_subs_medium_submitted_idx');
            $table->index(['utm_campaign', 'submitted_at'], 'marketing_subs_utm_campaign_submitted_idx');
            $table->index(['lead_reference', 'submitted_at'], 'marketing_subs_lead_submitted_idx');
            $table->index(['marketing_campaign_id', 'status', 'submitted_at'], 'marketing_subs_campaign_status_date_idx');
        });

        Schema::table('marketing_landing_page_utm_urls', function (Blueprint $table): void {
            $table->index(['marketing_campaign_id', 'created_at'], 'marketing_utm_campaign_created_idx');
        });

        // Backfill the new source dimension without changing any historical UTM
        // values. This keeps existing dashboards useful immediately after the
        // M-I migration. Landing tracking_source wins after utm_source, matching
        // LandingPageTrackingService::acquisitionSource().
        $pageSources = DB::table('marketing_landing_pages')
            ->pluck('tracking_source', 'id');

        DB::table('marketing_landing_page_views')
            ->select(['id', 'landing_page_id', 'utm_source', 'referrer'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($pageSources): void {
                foreach ($rows as $row) {
                    $source = trim((string) ($row->utm_source ?? ''));
                    if ($source === '') {
                        $source = trim((string) ($pageSources[$row->landing_page_id] ?? ''));
                    }
                    if ($source === '') {
                        $host = parse_url((string) ($row->referrer ?? ''), PHP_URL_HOST);
                        $source = is_string($host) && $host !== '' ? $host : 'direct';
                    }

                    DB::table('marketing_landing_page_views')
                        ->where('id', $row->id)
                        ->update(['source' => function_exists('mb_substr')
                            ? mb_substr($source, 0, 255)
                            : substr($source, 0, 255)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('marketing_landing_page_utm_urls', function (Blueprint $table): void {
            $table->dropIndex('marketing_utm_campaign_created_idx');
        });

        Schema::table('marketing_landing_page_submissions', function (Blueprint $table): void {
            $table->dropIndex('marketing_subs_source_submitted_idx');
            $table->dropIndex('marketing_subs_medium_submitted_idx');
            $table->dropIndex('marketing_subs_utm_campaign_submitted_idx');
            $table->dropIndex('marketing_subs_lead_submitted_idx');
            $table->dropIndex('marketing_subs_campaign_status_date_idx');
        });

        Schema::table('marketing_landing_page_views', function (Blueprint $table): void {
            $table->dropIndex('marketing_views_acquisition_source_viewed_idx');
            $table->dropIndex('marketing_views_source_viewed_idx');
            $table->dropIndex('marketing_views_medium_viewed_idx');
            $table->dropIndex('marketing_views_utm_campaign_viewed_idx');
            $table->dropColumn('source');
        });

        Schema::dropIfExists('marketing_audit_logs');
    }
};
