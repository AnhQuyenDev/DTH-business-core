<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracked_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_recipient_id')->constrained('campaign_recipients')->cascadeOnDelete();
            $table->text('original_url');
            $table->string('tracking_token')->unique();
            $table->unsignedInteger('click_count')->default(0);
            $table->timestamp('last_clicked_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracked_links');
    }
};
