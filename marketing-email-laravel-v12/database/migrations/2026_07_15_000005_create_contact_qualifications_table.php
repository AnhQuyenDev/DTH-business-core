<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_qualifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->unique()->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('assigned_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('status', 30)->default('new');
            $table->string('priority', 20)->default('normal');
            $table->unsignedTinyInteger('score')->nullable();
            $table->string('qualification_result', 50)->nullable();
            $table->string('service_interest')->nullable();
            $table->decimal('estimated_value', 18, 2)->nullable();
            $table->timestamp('first_contacted_at')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->timestamp('qualified_at')->nullable();
            $table->foreignId('qualified_by_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('unqualified_reason')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->unsignedBigInteger('converted_customer_id')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('assigned_staff_id');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_qualifications');
    }
};
