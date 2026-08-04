<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->longText('html_body')->nullable()->after('cta_text');
            $table->longText('css_body')->nullable()->after('html_body');
            $table->string('thumbnail_path')->nullable()->after('css_body');
        });

        // Migrate data: copy template html/css to each landing page that uses one
        $pages = DB::table('landing_pages')
            ->whereNotNull('landing_page_template_id')
            ->get(['id', 'landing_page_template_id']);

        foreach ($pages as $page) {
            $template = DB::table('landing_page_templates')
                ->where('id', $page->landing_page_template_id)
                ->first(['html_body', 'css_body']);

            if ($template) {
                DB::table('landing_pages')
                    ->where('id', $page->id)
                    ->update([
                        'html_body' => $template->html_body,
                        'css_body'  => $template->css_body,
                    ]);
            }
        }

        // Set a default html_body for landing pages without a template
        DB::table('landing_pages')
            ->whereNull('html_body')
            ->update(['html_body' => '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>{{page_title}}</title></head><body><h1>{{headline}}</h1><p>{{subheadline}}</p><div>{{content}}</div><div>{{form}}</div></body></html>']);

        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropForeign(['landing_page_template_id']);
            $table->dropColumn('landing_page_template_id');
        });

        Schema::dropIfExists('landing_page_templates');
    }

    public function down(): void
    {
        Schema::create('landing_page_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->longText('html_body');
            $table->longText('css_body')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('landing_pages', function (Blueprint $table) {
            $table->foreignId('landing_page_template_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn(['html_body', 'css_body', 'thumbnail_path']);
        });
    }
};
