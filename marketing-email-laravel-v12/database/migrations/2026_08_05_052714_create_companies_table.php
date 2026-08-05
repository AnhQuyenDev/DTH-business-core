<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_code')->unique();
            $table->string('legal_name');
            $table->string('normalized_name')->index();
            $table->string('tax_code')->nullable()->unique();
            $table->string('email_domain')->nullable()->index();
            $table->string('website')->nullable();
            $table->string('phone')->nullable();
            $table->string('normalized_phone')->nullable()->index();
            $table->string('industry')->nullable();
            $table->text('address')->nullable();
            $table->string('province')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('lifecycle_stage')->default('prospect')->index();
            $table->foreignId('account_owner_staff_id')
                ->nullable()
                ->constrained('staff')
                ->nullOnDelete();
            $table->foreignId('created_from_submission_id')
                ->nullable()
                ->constrained('landing_page_submissions')
                ->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
