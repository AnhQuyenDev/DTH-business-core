<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_template_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color', 32)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $categories = [
            [
                'name' => 'Tiếp thị',
                'slug' => 'marketing',
                'description' => 'Mẫu email tiếp thị, bản tin, giới thiệu sản phẩm',
                'color' => '#3B82F6',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Báo giá',
                'slug' => 'quotation',
                'description' => 'Mẫu email gửi báo giá cho khách hàng',
                'color' => '#F59E0B',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('email_template_categories')->insert($categories);
    }

    public function down(): void
    {
        Schema::dropIfExists('email_template_categories');
    }
};
