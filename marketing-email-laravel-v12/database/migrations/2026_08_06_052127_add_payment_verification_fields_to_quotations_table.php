<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            $table->timestamp('paid_at')
                ->nullable()
                ->after('payment_status');

            $table->foreignId('payment_verified_by_user_id')
                ->nullable()
                ->after('paid_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->text('payment_note')
                ->nullable()
                ->after('payment_verified_by_user_id');

            $table->index(
                ['payment_status', 'paid_at'],
                'quotations_payment_status_paid_at_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            $table->dropIndex(
                'quotations_payment_status_paid_at_index'
            );

            $table->dropForeign([
                'payment_verified_by_user_id',
            ]);

            $table->dropColumn([
                'paid_at',
                'payment_verified_by_user_id',
                'payment_note',
            ]);
        });
    }
};
