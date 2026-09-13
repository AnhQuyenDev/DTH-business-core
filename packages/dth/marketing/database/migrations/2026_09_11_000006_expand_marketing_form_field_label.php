<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_form_fields', function (Blueprint $table): void {
            $table->text('label')->change();
        });
    }

    public function down(): void
    {
        Schema::table('marketing_form_fields', function (Blueprint $table): void {
            $table->string('label')->change();
        });
    }
};
