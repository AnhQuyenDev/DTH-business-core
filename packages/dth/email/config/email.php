<?php

return [
    'queue' => env('DTH_EMAIL_QUEUE', 'emails'),
    'campaign_chunk_size' => (int) env('DTH_EMAIL_CAMPAIGN_CHUNK_SIZE', 200),

    'tracking' => [
        'enabled' => (bool) env('DTH_EMAIL_TRACKING_ENABLED', true),

        // Public base URL used inside outgoing emails. This must be reachable
        // by recipient mail clients / image proxies. It intentionally does not
        // depend on the current admin request host.
        'base_url' => env('DTH_EMAIL_TRACKING_BASE_URL', env('APP_URL')),
    ],

    'compliance' => [
        // Email Campaign is treated as marketing email. If a template does not
        // contain {{ unsubscribe_url }}, the module appends a footer automatically.
        // This is intentionally not exposed as a per-campaign or environment toggle.
        'footer_notice' => env(
            'DTH_EMAIL_FOOTER_NOTICE',
            'You are receiving this marketing email from :sender.'
        ),
        'unsubscribe_label' => env('DTH_EMAIL_UNSUBSCRIBE_LABEL', 'Unsubscribe'),
    ],
];
