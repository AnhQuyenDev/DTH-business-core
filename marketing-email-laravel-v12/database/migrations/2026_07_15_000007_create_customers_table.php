<?php

use App\Enums\Crm\CustomerConsentStatus;
use App\Enums\Crm\CustomerStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code', 50)->unique();
            $table->foreignId('contact_id')->unique()->constrained('contacts')->cascadeOnDelete();
            $table->string('customer_type', 20);
            $table->string('display_name');
            $table->string('email')->nullable()->index();
            $table->string('normalized_email')->nullable()->index();
            $table->string('phone', 30)->nullable();
            $table->string('normalized_phone', 30)->nullable()->index();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('acquisition_source')->nullable()->index();
            $table->string('consent_status', 30)->default(CustomerConsentStatus::Pending->value);
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamp('last_engaged_at')->nullable();
            $table->string('status', 30)->default(CustomerStatus::Potential->value);
            $table->string('lifecycle_stage', 50)->default('new_customer');
            $table->string('conversion_reason', 50)->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('converted_by_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('first_purchase_at')->nullable();
            $table->timestamp('latest_purchase_at')->nullable();
            $table->decimal('total_revenue', 18, 2)->default(0);
            $table->string('priority', 20)->default('normal');
            $table->timestamp('next_follow_up_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('consent_status');
            $table->index('customer_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
