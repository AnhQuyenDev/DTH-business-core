<?php

use App\Enums\Crm\StaffEmploymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('employee_code', 50)->unique();
            $table->string('full_name');
            $table->string('department', 50);
            $table->string('position', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('employment_status', 30)->default(StaffEmploymentStatus::Active->value);
            $table->boolean('can_receive_customers')->default(true);
            $table->unsignedInteger('customer_capacity')->nullable();
            $table->decimal('distribution_weight', 8, 2)->default(1.00);
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('department');
            $table->index('employment_status');
            $table->index('can_receive_customers');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
