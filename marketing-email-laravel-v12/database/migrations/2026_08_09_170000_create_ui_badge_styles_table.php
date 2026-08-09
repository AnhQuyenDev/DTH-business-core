<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ui_badge_styles', function (Blueprint $table): void {
            $table->id();
            $table->string('category', 50);
            $table->string('key', 100);
            $table->string('color', 30)->default('gray');
            $table->timestamps();

            $table->unique(['category', 'key']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ui_badge_styles');
    }
};
