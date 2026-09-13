<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->date('start_date')->nullable()->index();
            $table->date('end_date')->nullable()->index();
            $table->decimal('budget', 18, 2)->nullable();
            $table->char('currency', 3)->default('VND');
            $table->text('notes')->nullable();

            // Generic ownership/context references deliberately avoid foreign
            // keys to CRM/Sales/Finance so this package can migrate standalone.
            $table->string('owner_type', 100)->nullable();
            $table->string('owner_reference', 191)->nullable();
            $table->json('context')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();

            $table->timestamps();

            $table->index(
                ['status', 'start_date', 'end_date'],
                'marketing_campaigns_status_period_idx',
            );
            $table->index(
                ['owner_type', 'owner_reference'],
                'marketing_campaigns_owner_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_campaigns');
    }
};
