<?php

return [
    'queue' => env('DTH_EMAIL_QUEUE', 'emails'),
    'campaign_chunk_size' => (int) env('DTH_EMAIL_CAMPAIGN_CHUNK_SIZE', 200),


    'analytics' => [
        // Short-lived cache for dashboard/report aggregates. Set to 0 to disable
        // while debugging or running exact real-time UAT checks.
        'cache_ttl_seconds' => (int) env('DTH_EMAIL_ANALYTICS_CACHE_TTL', 120),
        'default_range_days' => (int) env('DTH_EMAIL_ANALYTICS_DEFAULT_RANGE_DAYS', 30),
        'max_range_days' => (int) env('DTH_EMAIL_ANALYTICS_MAX_RANGE_DAYS', 366),
        'max_hourly_range_days' => (int) env('DTH_EMAIL_ANALYTICS_MAX_HOURLY_RANGE_DAYS', 14),

        // Capability-aware reporting prevents unsupported SMTP provider events
        // from being presented as real 0% delivery/bounce/complaint metrics.
        'transport_capabilities' => [
            'smtp' => [
                'sent' => true,
                'open' => true,
                'click' => true,
                'unsubscribe' => true,
                'delivery' => false,
                'bounce' => false,
                'complaint' => false,
            ],

            // Safe fallback for a provider that has not registered its own
            // capabilities yet. Future SES/SendGrid/Postmark adapters can
            // override these values through published configuration.
            'default' => [
                'sent' => true,
                'open' => true,
                'click' => true,
                'unsubscribe' => true,
                'delivery' => false,
                'bounce' => false,
                'complaint' => false,
            ],
        ],
    ],

    'reports' => [
        // Management exports are generated through authenticated GET routes so
        // dashboard/report downloads do not depend on Livewire actions.
        'max_campaign_export_rows' => (int) env('DTH_EMAIL_REPORT_MAX_CAMPAIGNS', 5000),
        'max_recipient_export_rows' => (int) env('DTH_EMAIL_REPORT_MAX_RECIPIENTS', 100000),
        'xlsx_temp_directory' => env('DTH_EMAIL_XLSX_TEMP_DIRECTORY', storage_path('app/private/dth-email-exports')),
        'pdf' => [
            'enabled' => (bool) env('DTH_EMAIL_PDF_REPORTS_ENABLED', true),
            'paper' => env('DTH_EMAIL_PDF_PAPER', 'a4'),
            'orientation' => env('DTH_EMAIL_PDF_ORIENTATION', 'landscape'),
        ],
    ],

    'operations' => [
        // A heartbeat older than this threshold is reported unhealthy.
        'heartbeat_stale_seconds' => (int) env('DTH_EMAIL_HEARTBEAT_STALE_SECONDS', 180),

        // A reserved quota slot is automatically released if a worker dies
        // before the SMTP attempt finishes.
        'quota_reservation_ttl_minutes' => (int) env('DTH_EMAIL_QUOTA_RESERVATION_TTL_MINUTES', 30),
    ],

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
