<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'sales_opportunities',
            function (Blueprint $table): void {
                $table->unique(
                    'lead_id',
                    'sales_opportunities_lead_id_unique'
                );
            }
        );
    }

    public function down(): void
    {
        /*
         * Tạo index thường trước để foreign key lead_id
         * không phụ thuộc vào unique index sắp bị xóa.
         */
        if (! Schema::hasIndex(
            'sales_opportunities',
            'sales_opportunities_lead_id_index'
        )) {
            Schema::table(
                'sales_opportunities',
                function (Blueprint $table): void {
                    $table->index(
                        'lead_id',
                        'sales_opportunities_lead_id_index'
                    );
                }
            );
        }

        Schema::table(
            'sales_opportunities',
            function (Blueprint $table): void {
                $table->dropUnique(
                    'sales_opportunities_lead_id_unique'
                );
            }
        );
    }
};
