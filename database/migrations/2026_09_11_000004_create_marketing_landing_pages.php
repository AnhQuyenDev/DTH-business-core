<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_landing_pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('marketing_campaign_id')
                ->nullable()
                ->constrained('marketing_campaigns')
                ->nullOnDelete();
            $table->foreignId('personal_form_template_id')
                ->nullable()
                ->constrained('marketing_form_templates')
                ->nullOnDelete();
            $table->foreignId('business_form_template_id')
                ->nullable()
                ->constrained('marketing_form_templates')
                ->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('page_title')->nullable();
            $table->string('headline')->nullable();
            $table->string('subheadline')->nullable();
            $table->string('cta_text')->nullable();
            $table->longText('content')->nullable();
            $table->longText('html_body')->nullable();
            $table->longText('css_body')->nullable();
            $table->json('theme_tokens')->nullable();
            $table->string('service_reference')->nullable()->index();
            $table->json('package_references')->nullable();
            $table->json('catalog_snapshot')->nullable();
            $table->string('tracking_source')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            $table->index(
                ['marketing_campaign_id', 'status'],
                'marketing_landing_pages_campaign_status_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_landing_pages');
    }
};
