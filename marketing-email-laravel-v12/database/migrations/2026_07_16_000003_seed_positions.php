<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $positions = [
            ['title' => 'Quản trị viên', 'department' => 'admin', 'sort_order' => 1],
            ['title' => 'Trưởng phòng Marketing', 'department' => 'marketing', 'sort_order' => 2],
            ['title' => 'Nhân viên Marketing', 'department' => 'marketing', 'sort_order' => 3],
            ['title' => 'Trưởng phòng CSKH', 'department' => 'customer_service', 'sort_order' => 4],
            ['title' => 'Nhân viên CSKH', 'department' => 'customer_service', 'sort_order' => 5],
            ['title' => 'Trưởng phòng Kinh doanh', 'department' => 'sales', 'sort_order' => 6],
            ['title' => 'Nhân viên Kinh doanh', 'department' => 'sales', 'sort_order' => 7],
        ];

        foreach ($positions as $pos) {
            DB::table('positions')->insert([
                'title' => $pos['title'],
                'department' => $pos['department'],
                'description' => null,
                'sort_order' => $pos['sort_order'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('positions')->truncate();
    }
};
