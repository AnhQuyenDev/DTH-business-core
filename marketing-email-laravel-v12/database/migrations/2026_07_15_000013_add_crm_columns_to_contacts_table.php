<?php

use App\Enums\Crm\ContactType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('contact_type', 20)->default(ContactType::Personal->value)->after('id');
            $table->string('normalized_email')->nullable()->index()->after('email');
            $table->string('normalized_phone', 30)->nullable()->index()->after('phone');
            $table->timestamp('email_verified_at')->nullable()->after('normalized_email');
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            $table->string('form_source_type', 50)->nullable()->after('source');
            $table->unsignedBigInteger('form_source_id')->nullable()->after('form_source_type');
            $table->json('raw_data')->nullable()->after('form_source_id');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn([
                'contact_type',
                'normalized_email',
                'normalized_phone',
                'email_verified_at',
                'phone_verified_at',
                'form_source_type',
                'form_source_id',
                'raw_data',
            ]);
        });
    }
};
