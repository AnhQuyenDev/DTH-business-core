<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_sending_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sending_domain_id')->nullable()
                ->constrained('email_sending_domains')->nullOnDelete();
            $table->string('name');
            $table->string('provider', 50)->default('smtp');
            $table->string('from_name')->nullable();
            $table->string('from_email');
            $table->string('reply_to')->nullable();
            $table->longText('encrypted_config');
            $table->unsignedInteger('daily_limit')->nullable();
            $table->unsignedInteger('hourly_limit')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['provider', 'status']);
            $table->index('from_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_sending_accounts');
    }
};
