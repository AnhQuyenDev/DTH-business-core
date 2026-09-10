<?php

namespace Dth\Email\Console\Commands;

use Dth\Email\Enums\EmailCampaignStatus;
use Dth\Email\Models\EmailCampaign;
use Dth\Email\Services\CampaignService;
use Illuminate\Console\Command;

class ProcessScheduledCampaignsCommand extends Command
{
    protected $signature = 'email:campaigns:process';
    protected $description = 'Dispatch due scheduled email campaigns.';

    public function handle(CampaignService $campaigns): int
    {
        EmailCampaign::query()
            ->where('status', EmailCampaignStatus::Scheduled->value)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('id')
            ->each(fn (EmailCampaign $campaign) => $campaigns->start($campaign));

        return self::SUCCESS;
    }
}
