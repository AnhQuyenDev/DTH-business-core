<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_campaigns', function (Blueprint $table): void {
            $table->json('service_references')->nullable()->after('notes');
            $table->json('service_snapshot')->nullable()->after('service_references');
            $table->timestamp('status_changed_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('marketing_campaigns', function (Blueprint $table): void {
            $table->dropColumn([
                'service_references',
                'service_snapshot',
                'status_changed_at',
            ]);
        });
    }
};
