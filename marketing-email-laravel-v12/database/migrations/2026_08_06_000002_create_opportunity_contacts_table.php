<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_contacts', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('opportunity_id')
                ->constrained('sales_opportunities')
                ->cascadeOnDelete();

            $table->foreignId('contact_id')
                ->constrained('contacts')
                ->cascadeOnDelete();

            $table->string('role')->default('other');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['opportunity_id', 'contact_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_contacts');
    }
};
