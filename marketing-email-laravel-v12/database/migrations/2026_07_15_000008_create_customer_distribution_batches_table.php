<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_distribution_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code', 50)->unique();
            $table->string('type', 30);
            $table->string('status', 20)->default('draft');
            $table->foreignId('source_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('effective_from');
            $table->timestamp('effective_until')->nullable();
            $table->string('strategy', 30)->default('least_loaded');
            $table->unsignedInteger('total_customers')->default(0);
            $table->unsignedInteger('total_assigned')->default(0);
            $table->foreignId('initiated_by_user_id')->constrained('users');
            $table->timestamp('completed_at')->nullable();
            $table->text('note')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_distribution_batches');
    }
};
