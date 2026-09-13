<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_landing_pages', function (Blueprint $table): void {
            $table->json('auto_tag_names')->nullable()->after('tracking_source');
            $table->json('auto_list_names')->nullable()->after('auto_tag_names');
            $table->boolean('auto_create_tags')->default(false)->after('auto_list_names');
            $table->boolean('auto_create_lists')->default(false)->after('auto_create_tags');
            $table->boolean('auto_create_segment')->default(false)->after('auto_create_lists');
        });

        Schema::table('marketing_form_templates', function (Blueprint $table): void {
            $table->json('auto_tag_names')->nullable()->after('redirect_url');
            $table->json('auto_list_names')->nullable()->after('auto_tag_names');
            $table->boolean('auto_create_tags')->default(false)->after('auto_list_names');
            $table->boolean('auto_create_lists')->default(false)->after('auto_create_tags');
            $table->boolean('auto_create_segment')->default(false)->after('auto_create_lists');
        });

        Schema::create('marketing_landing_page_utm_urls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('landing_page_id')
                ->constrained('marketing_landing_pages')
                ->cascadeOnDelete();
            $table->foreignId('marketing_campaign_id')
                ->nullable()
                ->constrained('marketing_campaigns')
                ->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('utm_source')->nullable()->index();
            $table->string('utm_medium')->nullable()->index();
            $table->string('utm_campaign')->nullable()->index();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();
            $table->text('url');
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            $table->index(
                ['landing_page_id', 'created_at'],
                'marketing_utm_page_created_idx',
            );
        });

        Schema::create('marketing_campaign_email_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('marketing_campaign_id')
                ->constrained('marketing_campaigns')
                ->cascadeOnDelete();
            $table->string('email_campaign_reference');
            $table->string('display_name')->nullable();
            $table->string('status_snapshot')->nullable();
            $table->text('admin_url_snapshot')->nullable();
            $table->json('metrics_snapshot')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            $table->unique(
                ['marketing_campaign_id', 'email_campaign_reference'],
                'marketing_campaign_email_link_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_campaign_email_links');
        Schema::dropIfExists('marketing_landing_page_utm_urls');

        Schema::table('marketing_form_templates', function (Blueprint $table): void {
            $table->dropColumn([
                'auto_tag_names',
                'auto_list_names',
                'auto_create_tags',
                'auto_create_lists',
                'auto_create_segment',
            ]);
        });

        Schema::table('marketing_landing_pages', function (Blueprint $table): void {
            $table->dropColumn([
                'auto_tag_names',
                'auto_list_names',
                'auto_create_tags',
                'auto_create_lists',
                'auto_create_segment',
            ]);
        });
    }
};
