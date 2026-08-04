<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_book_access_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_book_id')->constrained()->cascadeOnDelete();
            $table->string('access_type', 30)->default('all');
            $table->string('role', 30)->nullable();
            $table->string('department', 50)->nullable();
            $table->foreignId('staff_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->boolean('can_view')->default(true);
            $table->boolean('can_create_quotation')->default(false);
            $table->string('discount_limit_type', 20)->nullable();
            $table->decimal('discount_limit_value', 18, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_book_access_rules');
    }
};
