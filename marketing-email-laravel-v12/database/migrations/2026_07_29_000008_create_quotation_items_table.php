<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_package_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('price_book_item_id')->nullable()->constrained()->nullOnDelete();

            $table->string('service_code_snapshot', 30);
            $table->string('service_name_snapshot', 255);
            $table->string('package_code_snapshot', 30)->nullable();
            $table->string('package_name_snapshot', 255);
            $table->text('description_snapshot')->nullable();
            $table->text('scope_snapshot')->nullable();
            $table->text('terms_snapshot')->nullable();

            $table->string('unit', 50)->default('tháng');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 18, 2)->default(0);

            $table->string('discount_type', 20)->nullable();
            $table->decimal('discount_value', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);

            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('vat_amount', 18, 2)->default(0);
            $table->decimal('line_subtotal', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
