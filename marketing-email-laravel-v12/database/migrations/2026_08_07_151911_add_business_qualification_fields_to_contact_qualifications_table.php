<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_qualifications', function (Blueprint $table) {
            $table->string('budget_status', 30)
                ->nullable()
                ->after('estimated_value');

            $table->decimal('budget_amount', 18, 2)
                ->nullable()
                ->after('budget_status');

            $table->string('purchase_timeline', 30)
                ->nullable()
                ->after('budget_amount');

            $table->string('decision_role', 30)
                ->nullable()
                ->after('purchase_timeline');

            $table->text('qualification_note')
                ->nullable()
                ->after('decision_role');
        });
    }

    public function down(): void
    {
        Schema::table('contact_qualifications', function (Blueprint $table) {
            $table->dropColumn([
                'budget_status',
                'budget_amount',
                'purchase_timeline',
                'decision_role',
                'qualification_note',
            ]);
        });
    }
};