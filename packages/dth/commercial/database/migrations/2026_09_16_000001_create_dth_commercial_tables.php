<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('commercial_services')) {
            Schema::create('commercial_services', function (Blueprint $table): void {
                $table->id();
                $table->string('service_code', 50)->unique();
                $table->string('name');
                $table->string('slug')->nullable()->unique();
                $table->text('description')->nullable();
                $table->text('default_scope')->nullable();
                $table->text('default_terms')->nullable();
                $table->string('status', 30)->default('active')->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->unsignedBigInteger('legacy_sales_id')->nullable()->unique();
                $table->json('metadata')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('commercial_service_packages')) {
            Schema::create('commercial_service_packages', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('service_id')->constrained('commercial_services')->cascadeOnDelete();
                $table->string('package_code', 50)->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('audience_type', 20)->default('both');
                $table->unsignedInteger('billing_period')->nullable();
                $table->string('billing_period_unit', 20)->nullable();
                $table->string('unit', 50)->default('package');
                $table->unsignedInteger('default_quantity')->default(1);
                $table->string('status', 30)->default('active')->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->unsignedBigInteger('legacy_sales_id')->nullable()->unique();
                $table->json('metadata')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['service_id', 'status']);
            });
        }

        if (! Schema::hasTable('commercial_opportunities')) {
            Schema::create('commercial_opportunities', function (Blueprint $table): void {
                $table->id();
                $table->string('opportunity_code', 50)->unique();
                $table->string('lead_reference')->nullable()->index();
                $table->string('lead_code_snapshot')->nullable()->index();
                $table->string('contact_reference')->nullable()->index();
                $table->string('contact_name_snapshot')->nullable();
                $table->string('company_reference')->nullable()->index();
                $table->string('company_name_snapshot')->nullable();
                $table->string('assigned_employee_reference')->nullable()->index();
                $table->string('assigned_employee_name_snapshot')->nullable();
                $table->foreignId('service_id')->nullable()->constrained('commercial_services')->nullOnDelete();
                $table->string('service_reference')->nullable()->index();
                $table->string('service_name_snapshot')->nullable();
                $table->string('title');
                $table->string('stage', 30)->default('qualified')->index();
                $table->decimal('estimated_value', 18, 2)->nullable();
                $table->unsignedTinyInteger('probability')->default(50);
                $table->date('expected_close_date')->nullable();
                $table->timestamp('won_at')->nullable();
                $table->timestamp('lost_at')->nullable();
                $table->text('lost_reason')->nullable();
                $table->unsignedBigInteger('legacy_sales_id')->nullable()->unique();
                $table->json('metadata')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['stage', 'service_reference']);
            });
        }

        if (! Schema::hasTable('commercial_opportunity_interactions')) {
            Schema::create('commercial_opportunity_interactions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('opportunity_id')->constrained('commercial_opportunities')->cascadeOnDelete();
                $table->string('employee_reference')->nullable()->index();
                $table->string('interaction_type', 30);
                $table->string('subject');
                $table->text('content')->nullable();
                $table->string('outcome')->nullable();
                $table->timestamp('interaction_at')->nullable();
                $table->timestamp('next_follow_up_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['opportunity_id', 'interaction_at'], 'commercial_opportunity_interactions_opp_interaction_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_opportunity_interactions');
        Schema::dropIfExists('commercial_opportunities');
        Schema::dropIfExists('commercial_service_packages');
        Schema::dropIfExists('commercial_services');
    }
};
