<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropIndex(['phone']);
            $table->dropIndex(['company_name']);
            $table->dropIndex(['source']);
            $table->dropIndex(['normalized_email']);
            $table->dropIndex(['normalized_phone']);
            $table->dropColumn([
                'first_name',
                'last_name',
                'full_name',
                'email',
                'normalized_email',
                'email_verified_at',
                'phone',
                'normalized_phone',
                'phone_verified_at',
                'company_name',
                'job_title',
                'source',
                'form_source_type',
                'form_source_id',
                'raw_data',
                'notes',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('first_name', 255)->nullable()->after('id');
            $table->string('last_name', 255)->nullable()->after('first_name');
            $table->string('full_name', 255)->nullable()->after('last_name');
            $table->string('email', 255)->nullable()->unique()->after('full_name');
            $table->string('normalized_email', 255)->nullable()->index()->after('email');
            $table->timestamp('email_verified_at')->nullable()->after('normalized_email');
            $table->string('phone', 255)->nullable()->index()->after('email_verified_at');
            $table->string('normalized_phone', 30)->nullable()->index()->after('phone');
            $table->timestamp('phone_verified_at')->nullable()->after('normalized_phone');
            $table->string('company_name', 255)->nullable()->index()->after('phone_verified_at');
            $table->string('job_title', 255)->nullable()->after('company_name');
            $table->string('source', 255)->nullable()->index()->after('job_title');
            $table->string('form_source_type', 50)->nullable()->after('source');
            $table->unsignedBigInteger('form_source_id')->nullable()->after('form_source_type');
            $table->json('raw_data')->nullable()->after('form_source_id');
            $table->text('notes')->nullable()->after('raw_data');
        });
    }
};
