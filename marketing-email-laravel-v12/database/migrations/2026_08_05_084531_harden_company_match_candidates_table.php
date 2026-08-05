<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_match_candidates', function (Blueprint $table): void {
            $table->unique(
                ['submission_id', 'contact_id', 'suggested_company_id'],
                'company_match_candidates_unique'
            );

            $table->index(
                ['status', 'created_at'],
                'company_match_candidates_status_created_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('company_match_candidates', function (Blueprint $table): void {
            $table->dropUnique('company_match_candidates_unique');
            $table->dropIndex('company_match_candidates_status_created_index');
        });
    }
};
