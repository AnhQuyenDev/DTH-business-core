<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_payment_notice_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_notice_id')
                ->constrained('quotation_payment_notices')
                ->cascadeOnDelete();
            $table->string('disk', 50)->default('local');
            $table->string('file_path', 1024);
            $table->string('original_name', 255);
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('file_size');
            $table->char('sha256', 64);
            $table->timestamp('uploaded_at');
            $table->timestamps();

            $table->index('payment_notice_id');
            $table->index('sha256');
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->string('payment_code', 40)->nullable()->unique();
            $table->foreignId('quotation_id')->unique()->constrained('quotations')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained('sales_opportunities')->nullOnDelete();
            $table->foreignId('payment_notice_id')->nullable()->unique()->constrained('quotation_payment_notices')->nullOnDelete();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->foreignId('sales_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->decimal('amount', 18, 2);
            $table->decimal('net_amount', 18, 2);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->string('currency', 3)->default('VND');
            $table->string('payment_method', 30)->default('bank_transfer');
            $table->string('transfer_reference', 255)->nullable();
            $table->string('status', 30)->default('verified');
            $table->timestamp('paid_at');
            $table->timestamp('verified_at');
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'paid_at']);
            $table->index(['sales_staff_id', 'paid_at']);
            $table->index(['customer_id', 'paid_at']);
        });

        Schema::create('payment_revenue_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('quotation_item_id')->nullable()->constrained('quotation_items')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('service_package_id')->nullable()->constrained('service_packages')->nullOnDelete();
            $table->string('service_code_snapshot', 30)->nullable();
            $table->string('service_name_snapshot', 255);
            $table->string('package_code_snapshot', 30)->nullable();
            $table->string('package_name_snapshot', 255)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('net_amount', 18, 2);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('gross_amount', 18, 2);
            $table->timestamps();

            $table->unique(['payment_id', 'quotation_item_id'], 'payment_revenue_lines_payment_item_unique');
            $table->index(['service_id', 'payment_id']);
            $table->index(['service_package_id', 'payment_id']);
        });

        Schema::create('payment_attributions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->unique()->constrained('payments')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('landing_page_submission_id')->nullable()->constrained('landing_page_submissions')->nullOnDelete();
            $table->foreignId('landing_page_id')->nullable()->constrained('landing_pages')->nullOnDelete();
            $table->foreignId('marketing_campaign_id')->nullable()->constrained('marketing_campaigns')->nullOnDelete();
            $table->foreignId('email_campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->string('acquisition_source', 100)->nullable();
            $table->string('utm_source', 255)->nullable();
            $table->string('utm_medium', 255)->nullable();
            $table->string('utm_campaign', 255)->nullable();
            $table->string('utm_content', 255)->nullable();
            $table->string('utm_term', 255)->nullable();
            $table->text('referrer')->nullable();
            $table->string('attribution_model', 50)->default('lead_origin');
            $table->decimal('weight', 5, 4)->default(1);
            $table->json('attribution_snapshot')->nullable();
            $table->timestamps();

            $table->index(['marketing_campaign_id', 'utm_source']);
            $table->index(['landing_page_id', 'utm_source']);
            $table->index(['email_campaign_id', 'utm_source']);
            $table->index('acquisition_source');
        });

        Schema::create('payment_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->unique()->constrained('payments')->cascadeOnDelete();
            $table->string('receipt_code', 50)->unique();
            $table->string('disk', 50)->default('local');
            $table->string('file_path', 1024);
            $table->string('file_name', 255);
            $table->string('mime_type', 100)->default('application/pdf');
            $table->char('sha256', 64);
            $table->timestamp('generated_at');
            $table->json('snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_receipts');
        Schema::dropIfExists('payment_attributions');
        Schema::dropIfExists('payment_revenue_lines');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('quotation_payment_notice_files');
    }
};
