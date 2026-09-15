<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_departments', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('function_key', 50)->nullable()->index();
            $table->string('color', 30)->default('gray');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('hr_positions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('title')->unique();
            $table->string('group_key', 40)->default('professional')->index();
            $table->string('authority_level', 30)->default('member')->index();
            $table->string('function_key', 50)->nullable()->index();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('hr_employees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('employee_code', 50)->unique();
            $table->string('full_name');
            $table->string('email')->nullable()->index();
            $table->string('phone', 30)->nullable()->index();
            $table->foreignId('department_id')->nullable()->constrained('hr_departments')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('hr_positions')->nullOnDelete();
            $table->string('employment_status', 30)->default('active')->index();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hr_employee_availabilities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('status', 30)->index();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->index();
            $table->boolean('can_receive_new_work')->default(false);
            $table->boolean('can_support_existing_work')->default(false);
            $table->string('reason')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['employee_id', 'starts_at', 'ends_at'], 'hr_availability_employee_period_idx');
        });

        Schema::create('hr_employee_business_functions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('function_key', 50)->index();
            $table->string('authority_level', 30)->default('member')->index();
            $table->boolean('is_primary')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'function_key'], 'hr_employee_business_function_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employee_business_functions');
        Schema::dropIfExists('hr_employee_availabilities');
        Schema::dropIfExists('hr_employees');
        Schema::dropIfExists('hr_positions');
        Schema::dropIfExists('hr_departments');
    }
};
