<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sending_domains', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique();
            $table->string('status')->default('unknown')->index();
            $table->string('spf_status')->default('unknown')->index();
            $table->string('dkim_status')->default('unknown')->index();
            $table->string('dmarc_status')->default('unknown')->index();
            $table->text('notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sending_domains');
    }
};
