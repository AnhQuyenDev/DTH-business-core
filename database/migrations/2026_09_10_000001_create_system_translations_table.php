<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_translations', function (Blueprint $table): void {
            $table->id();
            $table->string('translation_key', 191);
            $table->string('locale', 10);
            $table->text('value')->nullable();
            $table->text('default_value')->nullable();
            $table->string('module', 100)->nullable();
            $table->string('context', 100)->nullable();
            $table->string('source', 30)->default('manual');
            $table->boolean('is_reviewed')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['translation_key', 'locale'], 'system_translations_key_locale_unique');
            $table->index(['module', 'locale'], 'system_translations_module_locale_idx');
            $table->index(['locale', 'is_reviewed'], 'system_translations_locale_reviewed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_translations');
    }
};
