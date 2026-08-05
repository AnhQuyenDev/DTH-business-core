<?php

namespace App\Jobs\Marketing;

use App\Enums\Marketing\CampaignStatus;
use App\Models\Marketing\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessScheduledCampaignsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Campaign::query()
            ->where('status', CampaignStatus::Scheduled->value)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->chunkById(50, function ($campaigns): void {
                foreach ($campaigns as $campaign) {
                    /** @var Campaign $campaign */
                    $campaign->update(['status' => CampaignStatus::Preparing->value]);

                    dispatch(new PrepareCampaignRecipientsJob($campaign->id))->onQueue('marketing');
                }
            });
    }
}
