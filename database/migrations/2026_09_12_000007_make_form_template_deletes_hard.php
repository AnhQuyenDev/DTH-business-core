<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marketing_form_templates')) {
            return;
        }

        // Previous M-C revisions used SoftDeletes. Purge those rows before the
        // model stops applying the deleted_at global scope, otherwise records
        // that users already deleted would become visible again.
        if (Schema::hasColumn('marketing_form_templates', 'deleted_at')) {
            DB::table('marketing_form_templates')
                ->whereNotNull('deleted_at')
                ->orderBy('id')
                ->delete();

            Schema::table('marketing_form_templates', function (Blueprint $table): void {
                $table->dropColumn('deleted_at');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('marketing_form_templates')) {
            return;
        }

        if (! Schema::hasColumn('marketing_form_templates', 'deleted_at')) {
            Schema::table('marketing_form_templates', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }
};
