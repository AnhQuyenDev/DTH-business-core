<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_form_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('audience_type', 20)->index();
            $table->string('status', 32)->default('draft')->index();
            $table->unsignedInteger('version')->default(1);
            $table->string('submit_button_text', 100)->default('Submit');
            $table->string('success_message')->nullable();
            $table->string('redirect_url')->nullable();
            $table->longText('html_body')->nullable();
            $table->json('schema')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('marketing_form_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('form_template_id')
                ->constrained('marketing_form_templates')
                ->cascadeOnDelete();
            $table->string('label');
            $table->string('field_key');
            $table->string('field_type', 32)->default('text');
            $table->string('placeholder')->nullable();
            $table->json('options')->nullable();
            $table->text('default_value')->nullable();
            $table->boolean('is_required')->default(false);
            $table->string('contact_mapping')->nullable();
            $table->string('validation_rules')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(
                ['form_template_id', 'field_key'],
                'marketing_form_fields_template_key_unique',
            );
            $table->index(
                ['form_template_id', 'sort_order'],
                'marketing_form_fields_order_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_form_fields');
        Schema::dropIfExists('marketing_form_templates');
    }
};
