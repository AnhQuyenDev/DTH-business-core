<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_match_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->nullable()
                ->constrained('landing_page_submissions')->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('suggested_company_id')->constrained('companies')->cascadeOnDelete();
            $table->unsignedTinyInteger('confidence_score');
            $table->string('matched_by');
            $table->string('status')->default('pending');
            $table->json('evidence')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_match_candidates');
    }
};
