<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_distribution_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_batch_id')->constrained('customer_distribution_batches')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('original_owner_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('assigned_staff_id')->constrained('staff');
            $table->string('assignment_type', 20);
            $table->string('result_status', 20)->default('pending');
            $table->string('reason', 255)->nullable();
            $table->foreignId('customer_assignment_id')->nullable()->constrained('customer_assignments')->nullOnDelete();
            $table->timestamps();

            $table->unique(['distribution_batch_id', 'customer_id'], 'c_dist_items_batch_customer_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_distribution_items');
    }
};
