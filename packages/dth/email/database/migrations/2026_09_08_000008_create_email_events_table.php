<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_id')
                ->constrained('email_messages')->cascadeOnDelete();
            $table->string('event_type', 32)->index();
            $table->string('provider_event_id')->nullable()->unique();
            $table->json('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['message_id', 'event_type', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_events');
    }
};
