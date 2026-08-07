<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_page_submissions', function (Blueprint $table): void {
            $table->foreignId('marketing_campaign_id')
                ->nullable()
                ->after('campaign_id')
                ->constrained('marketing_campaigns')
                ->nullOnDelete();
        });

        // Snapshot campaign hiện tại cho submission cũ khi còn suy luận được
        // qua Landing Page. Dữ liệu mới sẽ được ghi trực tiếp lúc submit.
        DB::table('landing_page_submissions')
            ->whereNull('marketing_campaign_id')
            ->select(['id', 'landing_page_id'])
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $marketingCampaignId = DB::table('landing_pages')
                        ->where('id', $row->landing_page_id)
                        ->value('marketing_campaign_id');

                    if ($marketingCampaignId === null) {
                        continue;
                    }

                    DB::table('landing_page_submissions')
                        ->where('id', $row->id)
                        ->update([
                            'marketing_campaign_id' => (int) $marketingCampaignId,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('landing_page_submissions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('marketing_campaign_id');
        });
    }
};
