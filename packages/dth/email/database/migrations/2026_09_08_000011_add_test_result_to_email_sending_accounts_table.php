<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('email_sending_accounts', function (Blueprint $table): void {
            $table->string('last_test_status', 32)->nullable()->after('last_tested_at');
            $table->text('last_test_error')->nullable()->after('last_test_status');
        });
    }

    public function down(): void
    {
        Schema::table('email_sending_accounts', function (Blueprint $table): void {
            $table->dropColumn(['last_test_status', 'last_test_error']);
        });
    }
};
