<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table): void {
            $table->string('default_color', 30)->nullable()->after('color');
        });

        DB::table('departments')
            ->whereNull('default_color')
            ->update(['default_color' => DB::raw('color')]);
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table): void {
            $table->dropColumn('default_color');
        });
    }
};
