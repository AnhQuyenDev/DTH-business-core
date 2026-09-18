<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_employees', function (Blueprint $table): void {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['position_id']);

            $table->foreign('department_id')
                ->references('id')
                ->on('hr_departments')
                ->restrictOnDelete();

            $table->foreign('position_id')
                ->references('id')
                ->on('hr_positions')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table): void {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['position_id']);

            $table->foreign('department_id')
                ->references('id')
                ->on('hr_departments')
                ->nullOnDelete();

            $table->foreign('position_id')
                ->references('id')
                ->on('hr_positions')
                ->nullOnDelete();
        });
    }
};
