<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('email')->index();
            $table->string('status')->default('pending')->index();
            $table->string('personalized_subject')->nullable();
            $table->longText('personalized_html')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamp('opened_at')->nullable()->index();
            $table->timestamp('clicked_at')->nullable()->index();
            $table->timestamp('failed_at')->nullable()->index();
            $table->text('failure_reason')->nullable();
            $table->string('unsubscribe_token')->unique();
            $table->string('tracking_token')->unique();
            $table->timestamps();
            $table->unique(['campaign_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');
    }
};