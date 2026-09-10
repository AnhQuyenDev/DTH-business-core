<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table): void {
            $table->string('template_key')->nullable()->unique()->after('category_id');
            $table->text('description')->nullable()->after('name');
            $table->json('sample_variables')->nullable()->after('text_body');
        });

        DB::table('email_templates')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->get()
            ->each(function (object $template): void {
                $base = Str::slug((string) $template->name, '.');
                $base = $base !== '' ? $base : 'template';
                $candidate = $base;
                $suffix = 2;

                while (DB::table('email_templates')->where('template_key', $candidate)->exists()) {
                    $candidate = $base.'.'.$suffix;
                    $suffix++;
                }

                DB::table('email_templates')
                    ->where('id', $template->id)
                    ->update(['template_key' => $candidate]);
            });
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table): void {
            $table->dropUnique(['template_key']);
            $table->dropColumn([
                'template_key',
                'description',
                'sample_variables',
            ]);
        });
    }
};
