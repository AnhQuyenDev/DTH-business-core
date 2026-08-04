<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_contact_profiles', function (Blueprint $table) {
            $table->dropForeign(['owner_user_id']);
            $table->dropIndex(['normalized_email']);
            $table->dropIndex(['normalized_phone']);
            $table->dropIndex(['source']);
        });

        Schema::table('personal_contact_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'full_name',
                'normalized_email',
                'email_verified_at',
                'normalized_phone',
                'phone_verified_at',
                'job_title',
                'source',
                'form_source_type',
                'form_source_id',
                'raw_data',
                'notes',
                'owner_user_id',
                'identity_number',
                'address_line',
                'preferred_contact_time',
                'service_interest',
                'expected_budget',
                'metadata',
            ]);
        });

        Schema::table('business_contact_profiles', function (Blueprint $table) {
            $table->dropForeign(['owner_user_id']);
            $table->dropIndex(['source']);
        });

        Schema::table('business_contact_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'source',
                'form_source_type',
                'form_source_id',
                'raw_data',
                'notes',
                'owner_user_id',
                'website',
                'company_size',
                'service_interest',
                'expected_budget',
                'invoice_required',
                'metadata',
                'contact_person_name',
            ]);
        });

        Schema::table('business_contact_profiles', function (Blueprint $table) {
            $table->string('ward', 100)->nullable()->after('company_address');
        });
    }

    public function down(): void
    {
        Schema::table('business_contact_profiles', function (Blueprint $table) {
            $table->dropColumn('ward');
        });

        Schema::table('personal_contact_profiles', function (Blueprint $table) {
            $table->string('full_name', 255)->nullable();
            $table->string('normalized_email', 255)->nullable()->index();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('normalized_phone', 30)->nullable()->index();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('job_title', 255)->nullable();
            $table->string('source', 255)->nullable()->index();
            $table->string('form_source_type', 50)->nullable();
            $table->unsignedBigInteger('form_source_id')->nullable();
            $table->json('raw_data')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('identity_number', 50)->nullable();
            $table->string('address_line')->nullable();
            $table->string('preferred_contact_time', 100)->nullable();
            $table->string('service_interest')->nullable();
            $table->decimal('expected_budget', 18, 2)->nullable();
            $table->json('metadata')->nullable();
        });

        Schema::table('business_contact_profiles', function (Blueprint $table) {
            $table->string('source', 255)->nullable()->index();
            $table->string('form_source_type', 50)->nullable();
            $table->unsignedBigInteger('form_source_id')->nullable();
            $table->json('raw_data')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('website')->nullable();
            $table->string('company_size', 50)->nullable();
            $table->string('service_interest')->nullable();
            $table->decimal('expected_budget', 18, 2)->nullable();
            $table->boolean('invoice_required')->default(false);
            $table->json('metadata')->nullable();
            $table->string('contact_person_name')->nullable();
        });
    }
};
