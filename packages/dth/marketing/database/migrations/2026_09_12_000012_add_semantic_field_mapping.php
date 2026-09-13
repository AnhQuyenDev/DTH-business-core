<?php

use Dth\Marketing\Services\SemanticMappingRepairService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_form_fields', function (Blueprint $table): void {
            $table->string('semantic_role', 80)->nullable()->after('contact_mapping')->index();
            $table->unsignedTinyInteger('semantic_confidence')->nullable()->after('semantic_role');
            $table->string('semantic_source', 20)->nullable()->after('semantic_confidence');
        });

        Schema::table('marketing_landing_page_submissions', function (Blueprint $table): void {
            $table->json('normalized_data')->nullable()->after('data');
        });

        // Best-effort, idempotent repair for data created before semantic mapping
        // existed. A dedicated command is also shipped for reruns on large data.
        try {
            app(SemanticMappingRepairService::class)->repairAll();
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function down(): void
    {
        Schema::table('marketing_landing_page_submissions', function (Blueprint $table): void {
            $table->dropColumn('normalized_data');
        });

        Schema::table('marketing_form_fields', function (Blueprint $table): void {
            $table->dropIndex(['semantic_role']);
            $table->dropColumn([
                'semantic_role',
                'semantic_confidence',
                'semantic_source',
            ]);
        });
    }
};
