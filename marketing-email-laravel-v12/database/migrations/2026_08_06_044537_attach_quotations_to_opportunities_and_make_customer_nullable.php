<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            $table->foreignId('opportunity_id')
                ->nullable()
                ->after('quotation_code')
                ->constrained('sales_opportunities')
                ->nullOnDelete();

            $table->foreignId('company_id')
                ->nullable()
                ->after('opportunity_id')
                ->constrained('companies')
                ->nullOnDelete();

            $table->foreignId('contact_id')
                ->nullable()
                ->after('company_id')
                ->constrained('contacts')
                ->nullOnDelete();
        });

        /*
         * customer_id hiện dùng cascadeOnDelete và NOT NULL.
         * Phải bỏ foreign key trước khi change().
         */
        Schema::table('quotations', function (Blueprint $table): void {
            $table->dropForeign(['customer_id']);
        });

        Schema::table('quotations', function (Blueprint $table): void {
            $table->unsignedBigInteger('customer_id')
                ->nullable()
                ->change();
        });

        Schema::table('quotations', function (Blueprint $table): void {
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->nullOnDelete();

            $table->index(
                ['opportunity_id', 'status'],
                'quotations_opportunity_status_index'
            );

            $table->index(
                ['company_id', 'status'],
                'quotations_company_status_index'
            );
        });
    }

    public function down(): void
    {
        /*
         * Không thể rollback về customer_id NOT NULL nếu đã có báo giá
         * Opportunity chưa chuyển Customer.
         */
        $unsafeRows = DB::table('quotations')
            ->whereNull('customer_id')
            ->count();

        if ($unsafeRows > 0) {
            throw new RuntimeException(
                'Cannot rollback Phase 7: quotations with NULL customer_id exist.'
            );
        }

        Schema::table('quotations', function (Blueprint $table): void {
            $table->dropIndex('quotations_opportunity_status_index');
            $table->dropIndex('quotations_company_status_index');

            $table->dropForeign(['opportunity_id']);
            $table->dropForeign(['company_id']);
            $table->dropForeign(['contact_id']);

            $table->dropForeign(['customer_id']);
        });

        Schema::table('quotations', function (Blueprint $table): void {
            $table->unsignedBigInteger('customer_id')
                ->nullable(false)
                ->change();
        });

        Schema::table('quotations', function (Blueprint $table): void {
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->cascadeOnDelete();

            $table->dropColumn([
                'opportunity_id',
                'company_id',
                'contact_id',
            ]);
        });
    }
};
