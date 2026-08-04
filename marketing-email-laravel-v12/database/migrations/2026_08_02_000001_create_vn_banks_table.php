<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vn_banks', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('short_name', 100);
            $table->string('name', 255);
            $table->string('swift_code', 20)->nullable();
            $table->string('logo', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vn_banks');
    }
};
