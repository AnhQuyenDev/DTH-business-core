<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_business_functions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('function_key', 50)->index();
            $table->string('authority_level', 30)->default('member')->index();
            $table->boolean('is_primary')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['staff_id', 'function_key']);
        });

        // Backfill the current Department + Position model so this migration
        // is non-breaking. From V1 onward, Department remains organization
        // structure while this table represents the business capabilities a
        // person can actually perform.
        $rows = DB::table('staff')
            ->leftJoin('departments', 'departments.id', '=', 'staff.department_id')
            ->leftJoin('positions', 'positions.id', '=', 'staff.position_id')
            ->whereNull('staff.deleted_at')
            ->get([
                'staff.id as staff_id',
                'departments.function_key',
                'positions.authority_level',
            ]);

        foreach ($rows as $row) {
            $function = trim((string) ($row->function_key ?? ''));

            if ($function === '' || in_array($function, ['admin', 'other'], true)) {
                continue;
            }

            DB::table('staff_business_functions')->updateOrInsert(
                [
                    'staff_id' => $row->staff_id,
                    'function_key' => $function,
                ],
                [
                    'authority_level' => $row->authority_level ?: 'member',
                    'is_primary' => true,
                    'is_active' => true,
                    'metadata' => json_encode(['backfilled_from_department' => true]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_business_functions');
    }
};
