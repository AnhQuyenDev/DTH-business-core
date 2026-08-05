<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->id();
            $table->string('lead_code')->unique();

            $table->foreignId('submission_id')
                ->nullable()
                ->unique()
                ->constrained('landing_page_submissions')
                ->nullOnDelete();

            $table->foreignId('contact_id')
                ->constrained('contacts')
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->nullOnDelete();

            $table->foreignId('assigned_staff_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->string('source')->default('landing_page');
            $table->string('source_detail')->nullable();
            $table->string('title');
            $table->string('service_interest')->nullable();
            $table->decimal('estimated_value', 18, 2)->nullable();
            $table->string('intake_status')->default('new')->index();

            $table->timestamp('assigned_at')->nullable();
            $table->foreignId('assigned_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('converted_to_opportunity_at')->nullable();
            $table->json('metadata')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['contact_id', 'intake_status']);
            $table->index(['company_id', 'intake_status']);
            $table->index(['assigned_staff_id', 'intake_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
