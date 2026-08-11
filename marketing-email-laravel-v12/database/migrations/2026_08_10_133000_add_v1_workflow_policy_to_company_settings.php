<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table): void {
            $table->string('quotation_approval_mode', 40)
                ->default('amount_or_discount')
                ->after('vietqr_api_key');
            $table->decimal('quotation_approval_amount_threshold', 18, 2)
                ->default(20000000)
                ->after('quotation_approval_mode');
            $table->decimal('quotation_approval_discount_threshold_percent', 8, 4)
                ->default(10)
                ->after('quotation_approval_amount_threshold');
            $table->string('quotation_confirmation_mode', 20)
                ->default('click')
                ->after('quotation_approval_discount_threshold_percent');
            $table->boolean('payment_evidence_required')
                ->default(false)
                ->after('quotation_confirmation_mode');
            $table->boolean('support_tickets_enabled')
                ->default(true)
                ->after('payment_evidence_required');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'quotation_approval_mode',
                'quotation_approval_amount_threshold',
                'quotation_approval_discount_threshold_percent',
                'quotation_confirmation_mode',
                'payment_evidence_required',
                'support_tickets_enabled',
            ]);
        });
    }
};
