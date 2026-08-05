<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_qualifications', function (Blueprint $table): void {
            $table->dropForeign(['contact_id']);

            $table->dropUnique('contact_qualifications_contact_id_unique');

            $table->index(
                'contact_id',
                'contact_qualifications_contact_id_index'
            );

            $table->foreign('contact_id')
                ->references('id')
                ->on('contacts')
                ->cascadeOnDelete();

            $table->foreignId('lead_id')
                ->nullable()
                ->after('id')
                ->constrained('leads')
                ->cascadeOnDelete();

            $table->unique(
                'lead_id',
                'contact_qualifications_lead_id_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('contact_qualifications', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('lead_id');

            $table->dropForeign(['contact_id']);
            $table->dropIndex('contact_qualifications_contact_id_index');

            $table->unique('contact_id');

            $table->foreign('contact_id')
                ->references('id')
                ->on('contacts')
                ->cascadeOnDelete();
        });
    }
};
