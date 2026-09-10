<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_campaign_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')
                ->constrained('email_campaigns')->cascadeOnDelete();
            $table->string('external_type', 100)->nullable();
            $table->unsignedBigInteger('external_id')->nullable();
            $table->string('email');
            $table->string('name')->nullable();
            $table->json('variables')->nullable();
            $table->string('status', 32)->default('pending')->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'email']);
            $table->index(['external_type', 'external_id']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaign_recipients');
    }
};
