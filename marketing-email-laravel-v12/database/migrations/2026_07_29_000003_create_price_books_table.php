<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_books', function (Blueprint $table) {
            $table->id();
            $table->string('price_book_code', 30)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('audience_type', 20)->default('both');
            $table->string('currency', 3)->default('VND');
            $table->string('tax_mode', 20)->default('tax_exclusive');
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->string('status', 20)->default('draft');
            $table->boolean('is_default')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_books');
    }
};
