<?php

namespace App\Support;

final class UtmOptions
{
    /**
     * @return array<string, string>
     */
    public static function source(): array
    {
        return [
            'facebook' => __('field.utm_source_options.facebook'),
            'instagram' => __('field.utm_source_options.instagram'),
            'zalo' => __('field.utm_source_options.zalo'),
            'tiktok' => __('field.utm_source_options.tiktok'),
            'youtube' => __('field.utm_source_options.youtube'),
            'linkedin' => __('field.utm_source_options.linkedin'),
            'x' => __('field.utm_source_options.x'),
            'threads' => __('field.utm_source_options.threads'),
            'telegram' => __('field.utm_source_options.telegram'),
            'pinterest' => __('field.utm_source_options.pinterest'),
            'reddit' => __('field.utm_source_options.reddit'),
            'website' => __('field.utm_source_options.website'),
            'email' => __('field.utm_source_options.email'),
            'other' => __('field.utm_source_options.other'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function medium(): array
    {
        return [
            'social' => __('field.utm_medium_options.social'),
            'paid_social' => __('field.utm_medium_options.paid_social'),
            'cpc' => __('field.utm_medium_options.cpc'),
            'cpv' => __('field.utm_medium_options.cpv'),
            'display' => __('field.utm_medium_options.display'),
            'video' => __('field.utm_medium_options.video'),
            'email' => __('field.utm_medium_options.email'),
            'influencer' => __('field.utm_medium_options.influencer'),
            'referral' => __('field.utm_medium_options.referral'),
            'organic' => __('field.utm_medium_options.organic'),
            'other' => __('field.utm_medium_options.other'),
        ];
    }
}
