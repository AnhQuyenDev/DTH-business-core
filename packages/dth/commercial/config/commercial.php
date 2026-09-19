<?php

return [
    'enabled' => (bool) env('DTH_COMMERCIAL_ENABLED', true),

    'features' => [
        'catalog' => true,
        'products' => true,
        'bundles' => true,
        // Backward-compatible flag name. 'bundles' takes precedence when present.
        'packages' => true,
        'opportunities' => true,
        'analytics' => true,
    ],

    'authorization' => [
        'mode' => env('DTH_COMMERCIAL_AUTHORIZATION_MODE', 'auto'),
        'abilities' => [
            'view' => 'commercial.view',
            'manage-catalog' => 'commercial.manage-catalog',
            'manage-opportunities' => 'commercial.manage-opportunities',
            'reports' => 'commercial.reports',
            'export' => 'commercial.export',
        ],
    ],

    // Legacy data is imported automatically only when the old Sales tables are
    // present in the same database. Existing Marketing snapshots stay readable
    // even when this package is later disabled or removed.
    'legacy_import' => [
        'enabled' => (bool) env('DTH_COMMERCIAL_IMPORT_LEGACY_SALES', true),
    ],
];
