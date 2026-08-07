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
            function (Blueprint $table) {
                $table->unique(
                    'lead_id',
                    'sales_opportunities_lead_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'sales_opportunities',
            function (Blueprint $table) {
                $table->dropUnique(
                    'sales_opportunities_lead_unique'
                );
            }
        );
}
};
