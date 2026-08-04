<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_page_submissions', function (Blueprint $table) {
            $table->string('submission_type', 20)->nullable()->after('id');
            $table->string('business_tax_code', 30)->nullable()->index()->after('data');
            $table->string('qualification_status', 30)->default('pending_review')->after('status');
            $table->foreignId('assigned_staff_id')->nullable()->constrained('staff')->nullOnDelete()->after('qualification_status');
            $table->timestamp('verified_at')->nullable()->after('assigned_staff_id');
            $table->foreignId('verified_by_staff_id')->nullable()->constrained('staff')->nullOnDelete()->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('landing_page_submissions', function (Blueprint $table) {
            $table->dropForeign(['assigned_staff_id']);
            $table->dropForeign(['verified_by_staff_id']);
            $table->dropColumn([
                'submission_type',
                'business_tax_code',
                'qualification_status',
                'assigned_staff_id',
                'verified_at',
                'verified_by_staff_id',
            ]);
        });
    }
};
