<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('invoice_status', 30)->default('not_requested')->after('status')->index();
            $table->string('invoice_provider', 50)->nullable()->after('invoice_status');
            $table->string('external_invoice_id')->nullable()->after('invoice_provider');
            $table->string('invoice_number')->nullable()->after('external_invoice_id');
            $table->text('invoice_url')->nullable()->after('invoice_number');
            $table->timestamp('invoice_issued_at')->nullable()->after('invoice_url');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['invoice_status']);
            $table->dropColumn([
                'invoice_status', 'invoice_provider', 'external_invoice_id',
                'invoice_number', 'invoice_url', 'invoice_issued_at',
            ]);
        });
    }
};
