<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->foreignId('marketing_campaign_id')->nullable()->after('created_by')->constrained('marketing_campaigns')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropForeign(['marketing_campaign_id']);
            $table->dropColumn('marketing_campaign_id');
        });
    }
};
