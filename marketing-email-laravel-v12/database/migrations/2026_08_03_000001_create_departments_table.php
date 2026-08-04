<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $departments = [
            ['code' => 'admin', 'name' => 'Quản trị', 'sort_order' => 1],
            ['code' => 'marketing', 'name' => 'Marketing', 'sort_order' => 2],
            ['code' => 'customer_service', 'name' => 'CSKH', 'sort_order' => 3],
            ['code' => 'sales', 'name' => 'Kinh doanh', 'sort_order' => 4],
            ['code' => 'technical', 'name' => 'Phòng kỹ thuật', 'sort_order' => 5],
            ['code' => 'email_service', 'name' => 'Dịch vụ Email', 'sort_order' => 6],
        ];

        foreach ($departments as $department) {
            DB::table('departments')->insert([
                'code' => $department['code'],
                'name' => $department['name'],
                'description' => null,
                'sort_order' => $department['sort_order'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
