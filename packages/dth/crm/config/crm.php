<?php
return [
    'enabled' => env('DTH_CRM_ENABLED', true),
    'features' => [
        'contacts' => true, 'companies' => true, 'leads' => true,
        'qualification' => true, 'customers' => true, 'distribution' => true,
        'staff' => true, 'analytics' => true,
    ],
    'authorization_mode' => env('DTH_CRM_AUTHORIZATION_MODE', 'auto'),
    'deduplication' => [
        'email' => true, 'phone' => true, 'tax_code' => true,
        'lead_window_days' => 30,
    ],
    'distribution' => ['strategy' => 'round_robin'],
    'customer_on_paid_only' => env('DTH_CRM_CUSTOMER_ON_PAID_ONLY', true),
    'integrations' => [
        'sales_handoff' => env('DTH_CRM_SALES_HANDOFF_ADAPTER'),
    ],
];
