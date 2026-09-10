<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_messages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('sending_account_id')->nullable()
                ->constrained('email_sending_accounts')->nullOnDelete();
            $table->foreignId('template_id')->nullable()
                ->constrained('email_templates')->nullOnDelete();
            $table->foreignId('campaign_recipient_id')->nullable()->unique()
                ->constrained('email_campaign_recipients')->nullOnDelete();
            $table->string('related_type', 100)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('from_name')->nullable();
            $table->string('from_email');
            $table->string('reply_to')->nullable();
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->string('subject');
            $table->longText('html_body')->nullable();
            $table->longText('text_body')->nullable();
            $table->string('status', 32)->default('queued')->index();
            $table->string('provider_message_id')->nullable()->index();
            $table->string('idempotency_key')->nullable()->unique();
            $table->uuid('tracking_token')->unique();
            $table->uuid('unsubscribe_token')->unique();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['related_type', 'related_id']);
            $table->index('recipient_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_messages');
    }
};
