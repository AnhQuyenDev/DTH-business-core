<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_contact_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->unique()->constrained('contacts')->cascadeOnDelete();
            $table->string('company_name');
            $table->string('tax_code', 30)->nullable()->index();
            $table->string('company_address', 500)->nullable();
            $table->string('legal_representative')->nullable();
            $table->string('contact_person_name')->nullable();
            $table->string('contact_position', 150)->nullable();
            $table->string('business_email')->nullable();
            $table->string('business_phone', 30)->nullable();
            $table->string('website')->nullable();
            $table->string('industry', 150)->nullable();
            $table->string('company_size', 50)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('service_interest')->nullable();
            $table->decimal('expected_budget', 18, 2)->nullable();
            $table->boolean('invoice_required')->default(false);
            $table->string('tax_verification_status', 30)->default('pending');
            $table->timestamp('tax_verified_at')->nullable();
            $table->string('tax_verification_provider', 100)->nullable();
            $table->json('tax_verification_data')->nullable();
            $table->text('tax_verification_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_contact_profiles');
    }
};
