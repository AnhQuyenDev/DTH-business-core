<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('email_template_categories', function (Blueprint $table): void {
            $table->dropColumn('sort_order');
        });

        Schema::table('email_templates', function (Blueprint $table): void {
            $table->dropColumn('sample_variables');
        });
    }

    public function down(): void
    {
        Schema::table('email_template_categories', function (Blueprint $table): void {
            $table->unsignedInteger('sort_order')->default(0)->after('description');
        });

        Schema::table('email_templates', function (Blueprint $table): void {
            $table->json('sample_variables')->nullable()->after('text_body');
        });
    }
};
