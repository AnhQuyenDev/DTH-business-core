<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('package_code', 30)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('audience_type', 20)->default('both');
            $table->unsignedInteger('billing_period')->nullable();
            $table->string('billing_period_unit', 20)->nullable();
            $table->string('unit', 50)->default('tháng');
            $table->unsignedInteger('default_quantity')->default(1);
            $table->string('status', 20)->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_packages');
    }
};
