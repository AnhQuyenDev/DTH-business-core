<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sending_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider');
            $table->string('from_name');
            $table->string('from_email');
            $table->string('reply_to')->nullable();
            $table->text('config_encrypted')->nullable();
            $table->unsignedInteger('daily_limit')->nullable();
            $table->unsignedInteger('hourly_limit')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sending_accounts');
    }
};
