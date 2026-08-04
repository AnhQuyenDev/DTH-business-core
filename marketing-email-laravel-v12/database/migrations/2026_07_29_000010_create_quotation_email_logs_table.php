<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->string('recipient_email', 255);
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->string('subject', 255);
            $table->longText('body_snapshot');
            $table->string('attachment_path', 255)->nullable();
            $table->string('provider_message_id', 255)->nullable();
            $table->string('status', 20)->default('draft');
            $table->text('error_message')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_email_logs');
    }
};
