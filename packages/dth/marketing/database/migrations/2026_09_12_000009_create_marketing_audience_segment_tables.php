<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_contact_lists', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type', 32)->default('newsletter')->index();
            $table->string('status', 32)->default('active')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('marketing_contact_list_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contact_list_id')
                ->constrained('marketing_contact_lists')
                ->cascadeOnDelete();
            $table->foreignId('source_submission_id')
                ->nullable()
                ->constrained('marketing_landing_page_submissions')
                ->nullOnDelete();
            $table->char('member_key', 64);
            $table->string('contact_reference')->nullable()->index();
            $table->string('normalized_email')->nullable()->index();
            $table->string('normalized_phone', 50)->nullable();
            $table->string('display_name')->nullable();
            $table->string('audience_type', 20)->nullable()->index();
            $table->string('status', 32)->default('subscribed')->index();
            $table->json('metadata')->nullable();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['contact_list_id', 'member_key'],
                'marketing_contact_list_member_unique',
            );
            $table->index(
                ['member_key', 'status'],
                'marketing_contact_members_key_status_idx',
            );
        });

        Schema::create('marketing_segments', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('rules')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->boolean('is_automatic')->default(false)->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamp('last_evaluated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_segments');
        Schema::dropIfExists('marketing_contact_list_members');
        Schema::dropIfExists('marketing_contact_lists');
    }
};
