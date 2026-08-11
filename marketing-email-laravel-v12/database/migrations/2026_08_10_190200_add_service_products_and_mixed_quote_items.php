<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->string('product_code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit', 50)->default('đơn vị');
            $table->unsignedInteger('default_quantity')->default(1);
            $table->string('status', 30)->default('active')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('service_package_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_package_id')->constrained('service_packages')->cascadeOnDelete();
            $table->foreignId('service_product_id')->constrained('service_products')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
            $table->unique(
                ['service_package_id', 'service_product_id'],
                'service_package_products_unique'
            );
        });

        Schema::table('price_book_items', function (Blueprint $table): void {
            $table->foreignId('service_product_id')->nullable()->after('service_package_id')
                ->constrained('service_products')->nullOnDelete();
            $table->index(['price_book_id', 'service_product_id']);
        });

        Schema::table('quotation_items', function (Blueprint $table): void {
            $table->foreignId('service_product_id')->nullable()->after('service_package_id')
                ->constrained('service_products')->nullOnDelete();
            $table->string('product_code_snapshot', 50)->nullable()->after('package_name_snapshot');
            $table->string('product_name_snapshot')->nullable()->after('product_code_snapshot');
            $table->string('item_type', 20)->default('package')->after('price_book_item_id');
        });


        Schema::table('payment_revenue_lines', function (Blueprint $table): void {
            $table->foreignId('service_product_id')->nullable()->after('service_package_id')
                ->constrained('service_products')->nullOnDelete();
            $table->string('product_name_snapshot')->nullable()->after('package_name_snapshot');
            $table->index(['service_product_id', 'payment_id']);
        });
    }

    public function down(): void
    {
        Schema::table('payment_revenue_lines', function (Blueprint $table): void {
            $table->dropForeign(['service_product_id']);
            $table->dropIndex(['service_product_id', 'payment_id']);
            $table->dropColumn(['service_product_id', 'product_name_snapshot']);
        });

        Schema::table('quotation_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('service_product_id');
            $table->dropColumn(['product_code_snapshot', 'product_name_snapshot', 'item_type']);
        });
        Schema::table('price_book_items', function (Blueprint $table): void {
            $table->dropForeign(['service_product_id']);
            $table->dropIndex(['price_book_id', 'service_product_id']);
            $table->dropColumn('service_product_id');
        });
        Schema::dropIfExists('service_package_products');
        Schema::dropIfExists('service_products');
    }
};
