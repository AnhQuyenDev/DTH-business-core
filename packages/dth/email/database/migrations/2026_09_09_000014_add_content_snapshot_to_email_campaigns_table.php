<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_campaigns', function (Blueprint $table): void {
            $table->longText('html_body')
                ->nullable()
                ->after('preheader');

            $table->longText('text_body')
                ->nullable()
                ->after('html_body');

            $table->timestamp('failed_at')
                ->nullable()
                ->after('completed_at');

            $table->text('failure_reason')
                ->nullable()
                ->after('failed_at');
        });

        DB::table('email_campaigns')
            ->orderBy('id')
            ->each(function ($campaign): void {
                $template = DB::table('email_templates')
                    ->where('id', $campaign->email_template_id)
                    ->first();

                if (! $template) {
                    return;
                }

                DB::table('email_campaigns')
                    ->where('id', $campaign->id)
                    ->update([
                        'html_body' => $template->html_body,
                        'text_body' => $template->text_body,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('email_campaigns', function (Blueprint $table): void {
            $table->dropColumn([
                'html_body',
                'text_body',
                'failed_at',
                'failure_reason',
            ]);
        });
    }
};