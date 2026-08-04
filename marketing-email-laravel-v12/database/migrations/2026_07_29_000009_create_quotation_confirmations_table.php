<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->string('confirmation_type', 30);
            $table->string('signer_name', 255);
            $table->string('signer_position', 255)->nullable();
            $table->string('signer_email', 255);
            $table->string('signer_phone', 30)->nullable();
            $table->string('confirmation_code', 64)->unique();
            $table->timestamp('otp_verified_at')->nullable();
            $table->timestamp('confirmed_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('confirmation_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_confirmations');
    }
};
