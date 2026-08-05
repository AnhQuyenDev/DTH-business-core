<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')
                ->constrained('leads')
                ->cascadeOnDelete();
            $table->foreignId('staff_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('activity_type', 30);
            $table->string('status', 30)->default('completed');
            $table->string('subject');
            $table->text('content')->nullable();
            $table->string('outcome')->nullable();
            $table->timestamp('activity_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'activity_at']);
            $table->index(['lead_id', 'status']);
            $table->index('next_follow_up_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_activities');
    }
};
