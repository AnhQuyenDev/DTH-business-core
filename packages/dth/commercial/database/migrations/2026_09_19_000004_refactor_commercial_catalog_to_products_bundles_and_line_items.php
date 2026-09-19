<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('commercial_opportunities') && ! Schema::hasColumn('commercial_opportunities', 'currency')) {
            Schema::table('commercial_opportunities', function (Blueprint $table): void {
                $table->string('currency', 3)->default('VND')->after('estimated_value')->index();
            });
        }

        if (! Schema::hasTable('commercial_products')) {
            Schema::create('commercial_products', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('service_id')->constrained('commercial_services')->cascadeOnDelete();
                $table->string('product_code', 50)->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('audience_type', 20)->default('both');
                $table->string('unit', 50)->default('item');
                $table->decimal('default_quantity', 12, 2)->default(1);
                $table->string('status', 30)->default('active')->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->unsignedBigInteger('legacy_service_package_id')->nullable()->unique();
                $table->json('metadata')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['service_id', 'status']);
            });
        }

        if (! Schema::hasTable('commercial_product_prices')) {
            Schema::create('commercial_product_prices', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_id')->constrained('commercial_products')->cascadeOnDelete();
                $table->string('price_code', 50);
                $table->string('currency', 3)->default('VND')->index();
                $table->unsignedInteger('billing_period')->nullable();
                $table->string('billing_period_unit', 20)->nullable();
                $table->decimal('price', 18, 2)->nullable();
                $table->decimal('renewal_price', 18, 2)->nullable();
                $table->decimal('setup_fee', 18, 2)->default(0);
                $table->boolean('is_default')->default(false)->index();
                $table->string('status', 30)->default('active')->index();
                $table->date('valid_from')->nullable();
                $table->date('valid_until')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['product_id', 'price_code']);
                $table->index(['product_id', 'currency', 'status']);
            });
        }

        if (! Schema::hasTable('commercial_bundles')) {
            Schema::create('commercial_bundles', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('primary_service_id')->nullable()->constrained('commercial_services')->nullOnDelete();
                $table->string('bundle_code', 50)->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('audience_type', 20)->default('both');
                $table->string('pricing_type', 30)->default('component_sum');
                $table->decimal('fixed_price', 18, 2)->nullable();
                $table->decimal('renewal_price', 18, 2)->nullable();
                $table->decimal('setup_fee', 18, 2)->default(0);
                $table->string('currency', 3)->default('VND')->index();
                $table->unsignedInteger('billing_period')->nullable();
                $table->string('billing_period_unit', 20)->nullable();
                $table->string('status', 30)->default('active')->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('metadata')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('updated_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['primary_service_id', 'status']);
            });
        }

        if (! Schema::hasTable('commercial_bundle_items')) {
            Schema::create('commercial_bundle_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('bundle_id')->constrained('commercial_bundles')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('commercial_products')->cascadeOnDelete();
                $table->foreignId('product_price_id')->nullable()->constrained('commercial_product_prices')->nullOnDelete();
                $table->decimal('quantity', 12, 2)->default(1);
                $table->boolean('required')->default(true);
                $table->decimal('price_override', 18, 2)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['bundle_id', 'product_id']);
            });
        }

        if (! Schema::hasTable('commercial_opportunity_items')) {
            Schema::create('commercial_opportunity_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('opportunity_id')->constrained('commercial_opportunities')->cascadeOnDelete();
                $table->string('line_key', 80)->nullable();
                $table->string('item_type', 20);
                $table->foreignId('product_id')->nullable()->constrained('commercial_products')->nullOnDelete();
                $table->foreignId('product_price_id')->nullable()->constrained('commercial_product_prices')->nullOnDelete();
                $table->foreignId('bundle_id')->nullable()->constrained('commercial_bundles')->nullOnDelete();
                $table->string('item_code_snapshot', 80)->nullable();
                $table->string('item_name_snapshot')->nullable();
                $table->string('service_name_snapshot')->nullable();
                $table->text('description_snapshot')->nullable();
                $table->decimal('quantity', 12, 2)->default(1);
                $table->string('unit_snapshot', 50)->nullable();
                $table->decimal('unit_price', 18, 2)->nullable();
                $table->decimal('discount_percent', 5, 2)->default(0);
                $table->decimal('discount_amount', 18, 2)->default(0);
                $table->decimal('setup_fee', 18, 2)->default(0);
                $table->decimal('subtotal', 18, 2)->default(0);
                $table->decimal('total', 18, 2)->default(0);
                $table->string('currency', 3)->default('VND')->index();
                $table->unsignedInteger('billing_period')->nullable();
                $table->string('billing_period_unit', 20)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->unique(['opportunity_id', 'line_key']);
                $table->index(['opportunity_id', 'item_type']);
            });
        }

        $this->migrateLegacyPackagesToProducts();
    }

    private function migrateLegacyPackagesToProducts(): void
    {
        if (! Schema::hasTable('commercial_service_packages') || ! Schema::hasTable('commercial_products')) {
            return;
        }

        $now = now();
        $packages = DB::table('commercial_service_packages')->orderBy('id')->get();

        foreach ($packages as $package) {
            $product = DB::table('commercial_products')
                ->where('legacy_service_package_id', $package->id)
                ->orWhere('product_code', (string) $package->package_code)
                ->first();

            $productData = [
                'service_id' => $package->service_id,
                'product_code' => (string) $package->package_code,
                'name' => (string) $package->name,
                'description' => $package->description,
                'audience_type' => $package->audience_type ?: 'both',
                'unit' => $package->unit ?: 'item',
                'default_quantity' => $package->default_quantity ?: 1,
                'status' => $package->status ?: 'active',
                'sort_order' => $package->sort_order ?: 0,
                'legacy_service_package_id' => $package->id,
                'metadata' => json_encode(['migrated_from' => 'commercial_service_packages'], JSON_UNESCAPED_UNICODE),
                'created_by' => $package->created_by,
                'updated_by' => $package->updated_by,
                'updated_at' => $package->updated_at ?: $now,
            ];

            if ($product) {
                DB::table('commercial_products')->where('id', $product->id)->update($productData);
                $productId = $product->id;
            } else {
                $productData['created_at'] = $package->created_at ?: $now;
                $productId = DB::table('commercial_products')->insertGetId($productData);
            }

            if (! Schema::hasTable('commercial_product_prices')) {
                continue;
            }

            $priceData = [
                'product_id' => $productId,
                'price_code' => 'LEGACY',
                'currency' => property_exists($package, 'currency') && $package->currency ? strtoupper((string) $package->currency) : 'VND',
                'billing_period' => $package->billing_period,
                'billing_period_unit' => $package->billing_period_unit,
                'price' => property_exists($package, 'price') ? $package->price : null,
                'renewal_price' => property_exists($package, 'renewal_price') ? $package->renewal_price : null,
                'setup_fee' => property_exists($package, 'setup_fee') && $package->setup_fee !== null ? $package->setup_fee : 0,
                'is_default' => true,
                'status' => $package->status ?: 'active',
                'metadata' => json_encode(['migrated_from_service_package_id' => $package->id], JSON_UNESCAPED_UNICODE),
                'updated_at' => $package->updated_at ?: $now,
            ];

            $existingPrice = DB::table('commercial_product_prices')
                ->where('product_id', $productId)
                ->where('price_code', 'LEGACY')
                ->first();

            if ($existingPrice) {
                DB::table('commercial_product_prices')->where('id', $existingPrice->id)->update($priceData);
            } else {
                $priceData['created_at'] = $package->created_at ?: $now;
                DB::table('commercial_product_prices')->insert($priceData);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_opportunity_items');
        Schema::dropIfExists('commercial_bundle_items');
        Schema::dropIfExists('commercial_bundles');
        Schema::dropIfExists('commercial_product_prices');
        Schema::dropIfExists('commercial_products');

        if (Schema::hasTable('commercial_opportunities') && Schema::hasColumn('commercial_opportunities', 'currency')) {
            Schema::table('commercial_opportunities', function (Blueprint $table): void {
                $table->dropColumn('currency');
            });
        }
    }
};
