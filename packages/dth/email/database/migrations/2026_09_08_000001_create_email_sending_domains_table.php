<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_sending_domains', function (Blueprint $table): void {
            $table->id();
            $table->string('domain')->unique();
            $table->string('status', 32)->default('pending')->index();
            $table->string('spf_status', 32)->default('pending');
            $table->string('dkim_status', 32)->default('pending');
            $table->string('dmarc_status', 32)->default('pending');
            $table->string('dkim_selector')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_sending_domains');
    }
};
