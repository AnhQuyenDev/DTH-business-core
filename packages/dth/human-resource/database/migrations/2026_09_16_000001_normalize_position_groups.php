<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('hr_positions')
            ->whereNotIn('group_key', ['leadership', 'management', 'professional', 'operations', 'temporary', 'other'])
            ->update(['group_key' => 'professional']);
    }

    public function down(): void
    {
        // Invalid legacy values cannot be restored safely.
    }
};