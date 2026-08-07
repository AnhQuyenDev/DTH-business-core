<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table): void {
            $table->foreignId('service_id')
                ->nullable()
                ->after('marketing_campaign_id')
                ->constrained('services')
                ->nullOnDelete();
        });

        Schema::create('landing_page_service_package', function (Blueprint $table): void {
            $table->foreignId('landing_page_id')
                ->constrained('landing_pages')
                ->cascadeOnDelete();
            $table->foreignId('service_package_id')
                ->constrained('service_packages')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary([
                'landing_page_id',
                'service_package_id',
            ], 'lp_service_package_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_page_service_package');

        Schema::table('landing_pages', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('service_id');
        });
    }
};
