<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_email_logs', function (Blueprint $table): void {
            $table->foreignId('sending_account_id')
                ->nullable()
                ->after('quotation_id')
                ->constrained('sending_accounts')
                ->nullOnDelete();

            $table->string('sender_email', 255)
                ->nullable()
                ->after('sending_account_id');

            $table->string('sender_name', 255)
                ->nullable()
                ->after('sender_email');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_email_logs', function (Blueprint $table): void {
            $table->dropForeign(['sending_account_id']);
            $table->dropColumn([
                'sending_account_id',
                'sender_email',
                'sender_name',
            ]);
        });
    }
};
