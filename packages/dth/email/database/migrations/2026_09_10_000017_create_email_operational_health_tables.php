<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_system_heartbeats', function (Blueprint $table): void {
            $table->id();
            $table->string('component', 64)->unique();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('email_quota_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sending_account_id')
                ->constrained('email_sending_accounts')->cascadeOnDelete();
            $table->foreignId('email_message_id')
                ->unique()
                ->constrained('email_messages')->cascadeOnDelete();
            $table->string('status', 16)->default('reserved')->index();
            $table->timestamp('reserved_at')->index();
            $table->timestamp('consumed_at')->nullable()->index();
            $table->timestamp('released_at')->nullable()->index();
            $table->timestamps();

            $table->index(['sending_account_id', 'status', 'reserved_at'], 'email_quota_account_status_reserved_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_quota_reservations');
        Schema::dropIfExists('email_system_heartbeats');
    }
};
