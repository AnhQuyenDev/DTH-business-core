<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_campaign_service', function (Blueprint $table): void {
            $table->foreignId('marketing_campaign_id')
                ->constrained('marketing_campaigns')
                ->cascadeOnDelete();
            $table->foreignId('service_id')
                ->constrained('services')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->primary([
                'marketing_campaign_id',
                'service_id',
            ], 'marketing_campaign_service_primary');

            $table->index('service_id');
        });

        // Backfill an toàn: nếu Landing Page hiện tại đã biết dịch vụ thì
        // Campaign của Landing Page đó cũng được xem là đang quảng bá dịch vụ ấy.
        // Campaign cũ chưa có Landing Page/service sẽ được cấu hình thủ công trên UI.
        $now = now();

        DB::table('landing_pages')
            ->whereNotNull('marketing_campaign_id')
            ->whereNotNull('service_id')
            ->select(['marketing_campaign_id', 'service_id'])
            ->distinct()
            ->orderBy('marketing_campaign_id')
            ->chunk(200, function ($rows) use ($now): void {
                $payload = collect($rows)
                    ->map(fn ($row): array => [
                        'marketing_campaign_id' => (int) $row->marketing_campaign_id,
                        'service_id' => (int) $row->service_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->all();

                if ($payload !== []) {
                    DB::table('marketing_campaign_service')->insertOrIgnore($payload);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_campaign_service');
    }
};
