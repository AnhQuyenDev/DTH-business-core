<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table): void {
            $table->foreignId('category_id')->nullable()->after('name')->constrained('email_template_categories')->nullOnDelete();
            $table->index('category_id');
        });

        $categories = DB::table('email_template_categories')->pluck('id', 'slug')->toArray();

        if (! empty($categories)) {
            $templates = DB::table('email_templates')->select('id', 'category')->get();

            foreach ($templates as $template) {
                $slug = $template->category ?? 'marketing';
                $categoryId = $categories[$slug] ?? $categories['marketing'] ?? null;

                if ($categoryId) {
                    DB::table('email_templates')
                        ->where('id', $template->id)
                        ->update(['category_id' => $categoryId]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table): void {
            $table->dropForeign(['category_id']);
            $table->dropIndex(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
