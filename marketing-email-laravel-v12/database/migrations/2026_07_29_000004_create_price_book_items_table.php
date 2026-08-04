<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_book_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_package_id')->constrained()->cascadeOnDelete();
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->unsignedInteger('minimum_quantity')->nullable();
            $table->unsignedInteger('maximum_quantity')->nullable();
            $table->string('default_discount_type', 20)->nullable();
            $table->decimal('default_discount_value', 18, 2)->nullable();
            $table->decimal('maximum_discount_value', 18, 2)->nullable();
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->text('description')->nullable();
            $table->text('scope_override')->nullable();
            $table->text('terms_override')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_book_items');
    }
};
