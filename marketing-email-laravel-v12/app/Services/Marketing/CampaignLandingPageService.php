<?php

namespace App\Services\Marketing;

use App\Models\Marketing\Campaign;
use App\Models\Marketing\LandingPage;
use Illuminate\Support\Str;

class CampaignLandingPageService
{
    public function attach(Campaign $campaign, LandingPage $lp): void
    {
        $campaign->update(['landing_page_id' => $lp->id]);

        if (! $lp->campaign_id) {
            $lp->update(['campaign_id' => $campaign->id]);
        }
    }

    public function validateReadyToSend(Campaign $campaign): void
    {
        if ($campaign->landing_page_id) {
            $lp = $campaign->landingPage;
            if (! $lp || ! $lp->isPublished()) {
                throw new \InvalidArgumentException('Landing Page phải được publish trước khi gửi campaign.');
            }
        }
    }

    public function checkTemplateMissingLandingUrl(Campaign $campaign): ?string
    {
        $template = $campaign->relationLoaded('template') ? $campaign->template : $campaign->template()->first();
        if (! $template) {
            return null;
        }
        $body = $template->html_body.' '.($template->text_body ?? '');
        if (! Str::contains($body, '{{landing_page_url}}')) {
            return __('helper.template_missing_landing_url');
        }

        return null;
    }

    public function getCampaignLink(Campaign $campaign): string
    {
        $lp = $campaign->landingPage;

        $utmCampaign = trim((string) ($campaign->name ?? ''));
        if ($utmCampaign === '') {
            $utmCampaign = 'campaign-'.$campaign->id;
        }

        return route('marketing.landing-pages.public.show', [
            'slug' => $lp->slug,
            'cid' => $campaign->id,
            'utm_source' => 'email',
            'utm_medium' => 'email',
            'utm_campaign' => $utmCampaign,
        ]);
    }
}
