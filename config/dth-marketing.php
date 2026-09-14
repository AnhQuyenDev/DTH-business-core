<?php

return [
    'enabled' => (bool) env('DTH_MARKETING_ENABLED', true),

    'integrations' => [
        'audience_provider' => null,
        'lead_provider' => null,
        'catalog_provider' => null,
        'revenue_provider' => null,
        'email_marketing_bridge' => null,
    ],

    'features' => [
        'campaigns' => true,
        'form_templates' => true,
        'landing_pages' => true,
        'public_submission' => true,
        'audiences' => true,
        'segments' => true,
        'utm' => true,
        'email_bridge' => true,
        'analytics' => true,
    ],

    'analytics' => [
        'default_period' => '30d',
        'max_custom_days' => 366,
    ],

    'authorization' => [
        'mode' => env('DTH_MARKETING_AUTHORIZATION_MODE', 'auto'),
        'abilities' => [
            'view' => 'marketing.view',
            'manage' => 'marketing.manage',
            'view-reports' => 'marketing.view-reports',
            'export' => 'marketing.export',
            'process-submissions' => 'marketing.process-submissions',
        ],
    ],

    'security' => [
        'submission_rate_limit_per_minute' => (int) env('DTH_MARKETING_SUBMIT_PER_MINUTE', 10),
        'submission_rate_limit_per_hour' => (int) env('DTH_MARKETING_SUBMIT_PER_HOUR', 60),
        'duplicate_window_seconds' => (int) env('DTH_MARKETING_DUPLICATE_WINDOW_SECONDS', 60),
        'max_submission_payload_bytes' => (int) env('DTH_MARKETING_MAX_SUBMISSION_PAYLOAD_BYTES', 65536),
    ],

    'audit' => [
        'enabled' => (bool) env('DTH_MARKETING_AUDIT_ENABLED', true),
        'retention_days' => (int) env('DTH_MARKETING_AUDIT_RETENTION_DAYS', 365),
    ],
];
