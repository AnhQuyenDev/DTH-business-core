<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_interactions', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('opportunity_id')
                ->constrained('sales_opportunities')
                ->cascadeOnDelete();

            $table->foreignId('staff_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();

            $table->string('interaction_type');
            $table->string('subject');
            $table->text('content')->nullable();
            $table->string('outcome')->nullable();
            $table->timestamp('interaction_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_interactions');
    }
};
