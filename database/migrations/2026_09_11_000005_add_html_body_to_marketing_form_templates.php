<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('marketing_form_templates', 'html_body')) {
            Schema::table('marketing_form_templates', function (Blueprint $table): void {
                $table->longText('html_body')->nullable()->after('redirect_url');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('marketing_form_templates', 'html_body')) {
            Schema::table('marketing_form_templates', function (Blueprint $table): void {
                $table->dropColumn('html_body');
            });
        }
    }
};
