<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('landing_page_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('landing_form_template_id')->nullable()->constrained('form_templates')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('page_title')->nullable();
            $table->string('headline')->nullable();
            $table->string('subheadline')->nullable();
            $table->longText('content')->nullable();
            $table->string('cta_text')->nullable();
            $table->string('tracking_source')->nullable();
            $table->json('auto_tag_names')->nullable();
            $table->json('auto_list_names')->nullable();
            $table->boolean('auto_create_tags')->default(true);
            $table->boolean('auto_create_lists')->default(true);
            $table->boolean('auto_create_segment')->default(true);
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_pages');
    }
};
