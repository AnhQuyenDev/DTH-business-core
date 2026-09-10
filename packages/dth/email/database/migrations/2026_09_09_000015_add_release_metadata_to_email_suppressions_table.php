<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('email_suppressions', function (Blueprint $table): void {
            // Keep one row per suppression cycle so unsubscribe -> resubscribe ->
            // unsubscribe again does not overwrite the previous audit history.
            $table->dropUnique('email_suppressions_email_unique');
            $table->index('email');

            $table->timestamp('released_at')->nullable()->after('created_by')->index();
            $table->unsignedBigInteger('released_by')->nullable()->after('released_at')->index();
            $table->string('release_source', 100)->nullable()->after('released_by');
            $table->text('release_note')->nullable()->after('release_source');
        });
    }

    public function down(): void
    {
        Schema::table('email_suppressions', function (Blueprint $table): void {
            $table->dropIndex('email_suppressions_email_index');
            $table->dropIndex('email_suppressions_released_at_index');
            $table->dropIndex('email_suppressions_released_by_index');
            $table->dropColumn([
                'released_at',
                'released_by',
                'release_source',
                'release_note',
            ]);
            $table->unique('email');
        });
    }
};
