<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('interaction_type', 30);
            $table->string('subject')->nullable();
            $table->text('content');
            $table->string('outcome', 100)->nullable();
            $table->timestamp('interaction_at');
            $table->timestamp('next_follow_up_at')->nullable();
            $table->boolean('is_support_action')->default(false);
            $table->foreignId('original_owner_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'interaction_at']);
            $table->index(['staff_id', 'interaction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_interactions');
    }
};
