<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_opportunities', function (Blueprint $table): void {
            $table->id();
            $table->string('opportunity_code')->unique();

            $table->foreignId('lead_id')
                ->nullable()
                ->constrained('leads')
                ->nullOnDelete();

            $table->foreignId('company_id')
                ->nullable()
                ->constrained('companies')
                ->nullOnDelete();

            $table->foreignId('primary_contact_id')
                ->constrained('contacts')
                ->cascadeOnDelete();

            $table->foreignId('assigned_staff_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->foreignId('price_book_id')
                ->nullable()
                ->constrained('price_books')
                ->nullOnDelete();

            $table->string('title');
            $table->string('service_interest')->nullable();
            $table->string('stage')->default('qualified')->index();
            $table->decimal('estimated_value', 18, 2)->nullable();
            $table->unsignedTinyInteger('probability')->default(50);
            $table->date('expected_close_date')->nullable();
            $table->timestamp('won_at')->nullable();
            $table->timestamp('lost_at')->nullable();
            $table->text('lost_reason')->nullable();
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

            $table->index(['company_id', 'stage']);
            $table->index(['assigned_staff_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_opportunities');
    }
};
