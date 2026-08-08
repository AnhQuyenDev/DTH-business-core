<?php

return [
    'v2_enabled' => env('BUSINESS_FLOW_V2_ENABLED', false),
    'company_resolution_enabled' => env('COMPANY_RESOLUTION_ENABLED', false),
    'opportunity_quotation_enabled' => env('OPPORTUNITY_QUOTATION_ENABLED', false),
    'customer_on_paid_only' => env('CUSTOMER_ON_PAID_ONLY', false),
    'quotation_email_queue_enabled' => env('QUOTATION_EMAIL_QUEUE_ENABLED', false),
    'quotation_email_queue' => env('QUOTATION_EMAIL_QUEUE', 'quotations'),
    'timezone' => env(
        'BUSINESS_TIMEZONE',
        'Asia/Ho_Chi_Minh'
    ),
];
