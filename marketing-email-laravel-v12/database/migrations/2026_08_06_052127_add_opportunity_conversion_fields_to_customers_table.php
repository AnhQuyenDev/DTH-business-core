<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->foreignId('company_id')
                ->nullable()
                ->after('contact_id')
                ->constrained('companies')
                ->nullOnDelete();

            $table->foreignId('converted_from_opportunity_id')
                ->nullable()
                ->after('company_id')
                ->constrained('sales_opportunities')
                ->nullOnDelete();

            $table->unique(
                'converted_from_opportunity_id',
                'customers_converted_from_opportunity_unique'
            );

            /*
             * Một Company chỉ có một Customer doanh nghiệp.
             * MySQL vẫn cho phép nhiều NULL trong unique index,
             * nên Customer cá nhân không bị ảnh hưởng.
             */
            $table->unique(
                'company_id',
                'customers_company_id_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropUnique(
                'customers_converted_from_opportunity_unique'
            );

            $table->dropUnique(
                'customers_company_id_unique'
            );

            $table->dropForeign([
                'converted_from_opportunity_id',
            ]);

            $table->dropForeign([
                'company_id',
            ]);

            $table->dropColumn([
                'converted_from_opportunity_id',
                'company_id',
            ]);
        });
    }
};
