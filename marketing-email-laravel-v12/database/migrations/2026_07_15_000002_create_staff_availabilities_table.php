<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('can_receive_new_customers')->default(false);
            $table->boolean('can_support_customers')->default(false);
            $table->string('reason', 255)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('staff_id');
            $table->index('status');
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_availabilities');
    }
};
