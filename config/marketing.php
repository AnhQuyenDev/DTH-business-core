<?php

return [
    'enabled' => (bool) env('DTH_MARKETING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Integration adapters
    |--------------------------------------------------------------------------
    |
    | Marketing owns campaigns, landing pages, forms, submissions, audiences,
    | segments, UTM and attribution. CRM / Sales / Finance / Email capabilities
    | are consumed through contracts only. A root application or another module
    | may bind a contract in its service provider, or configure an adapter class
    | here. When no adapter exists the Null provider keeps Marketing bootable and
    | the UI must render the dependent capability as N/A instead of a fake zero.
    |
    */
    'integrations' => [
        'audience_provider' => null,
        'lead_provider' => null,
        'catalog_provider' => null,
        'revenue_provider' => null,
        'email_marketing_bridge' => null,
    ],

    /*
    | M-B through M-D are enabled together in this cumulative patch. M-E and
    | later acquisition/audience/UTM/analytics features remain disabled until
    | the combined M-B..M-D UAT baseline passes.
    */
    'features' => [
        'campaigns' => true,
        'form_templates' => true,
        'landing_pages' => true,
        'public_submission' => false,
        'audiences' => false,
        'segments' => false,
        'utm' => false,
        'email_bridge' => false,
        'analytics' => false,
    ],
];
