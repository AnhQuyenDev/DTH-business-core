<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_tracked_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('message_id')
                ->constrained('email_messages')->cascadeOnDelete();
            $table->text('original_url');
            $table->uuid('tracking_token')->unique();
            $table->unsignedBigInteger('click_count')->default(0);
            $table->timestamp('last_clicked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_tracked_links');
    }
};
