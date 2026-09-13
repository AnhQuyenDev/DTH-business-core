<?php

return [
    'enabled' => (bool) env('DTH_MARKETING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Integration adapters
    |--------------------------------------------------------------------------
    |
    | Marketing owns campaigns, landing pages, forms, submissions, audiences,
    | segments, UTM, attribution and analytics. CRM / Sales / Finance / Email
    | capabilities are consumed through contracts only. A root application or
    | another module may bind a contract in its service provider, or configure
    | an adapter class here. Missing capabilities must render N/A, never a fake 0.
    |
    */
    'integrations' => [
        'audience_provider' => null,
        'lead_provider' => null,
        'catalog_provider' => null,
        'revenue_provider' => null,
        'email_marketing_bridge' => null,
    ],

    /* M-A through M-I are enabled in the completed Marketing package. */
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

    /*
    | Authorization mode:
    | - auto: enforce host permissions when they exist, otherwise preserve the
    |   current authenticated-admin behavior of DTH Core.
    | - strict: every permission below must be granted by the host application.
    | - off: authenticated users are allowed (not recommended for production).
    */
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
