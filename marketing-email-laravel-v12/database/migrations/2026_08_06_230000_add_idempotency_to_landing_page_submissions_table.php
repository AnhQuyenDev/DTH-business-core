<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_page_submissions', function (Blueprint $table): void {
            $table->uuid('submission_token')
                ->nullable()
                ->after('landing_form_template_id');
            $table->char('payload_fingerprint', 64)
                ->nullable()
                ->after('submission_token');

            $table->unique(
                ['landing_page_id', 'submission_token'],
                'lp_submissions_page_token_unique'
            );
            $table->index(
                ['landing_page_id', 'payload_fingerprint', 'created_at'],
                'lp_submissions_fingerprint_lookup'
            );
        });
    }

    public function down(): void
    {
        Schema::table('landing_page_submissions', function (Blueprint $table): void {
            $table->dropUnique('lp_submissions_page_token_unique');
            $table->dropIndex('lp_submissions_fingerprint_lookup');
            $table->dropColumn([
                'submission_token',
                'payload_fingerprint',
            ]);
        });
    }
};
