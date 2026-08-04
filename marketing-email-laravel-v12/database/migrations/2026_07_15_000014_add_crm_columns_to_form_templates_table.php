<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            $table->string('audience_type', 20)->default('generic')->after('status');
            $table->unsignedInteger('version')->default(1)->after('audience_type');
            $table->boolean('is_system_template')->default(false)->after('version');
            $table->json('schema')->nullable()->after('is_system_template');
        });
    }

    public function down(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            $table->dropColumn(['audience_type', 'version', 'is_system_template', 'schema']);
        });
    }
};
