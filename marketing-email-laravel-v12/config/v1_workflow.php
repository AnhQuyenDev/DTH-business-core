<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Quotation approval policy
    |--------------------------------------------------------------------------
    | none              : every complete quotation is auto-approved.
    | always            : every quotation needs a different Sales manager.
    | amount_threshold  : approval when grand total >= threshold.
    | discount_threshold: approval when effective discount % >= threshold.
    | amount_or_discount: approval when either threshold is reached.
    */
    'quotation_approval' => [
        'mode' => env('V1_QUOTATION_APPROVAL_MODE', 'amount_or_discount'),
        'amount_threshold' => (float) env('V1_QUOTATION_APPROVAL_AMOUNT', 20000000),
        'discount_threshold_percent' => (float) env('V1_QUOTATION_APPROVAL_DISCOUNT_PERCENT', 10),
    ],

    /*
    | Public quotation confirmation. V1 defaults to a simple click-confirm
    | flow. OTP implementation is intentionally retained behind this policy so
    | it can be re-enabled later without rebuilding the quotation domain.
    */
    'customer_confirmation' => [
        'mode' => env('V1_QUOTATION_CONFIRMATION_MODE', 'click'), // click|otp
    ],

    /*
    | Payment evidence is useful for manual reconciliation, but it should not
    | be mandatory for every customer in a lean startup flow. The upload field
    | remains available and can be made mandatory again by configuration.
    */
    'payment_notice' => [
        'evidence_required' => filter_var(
            env('V1_PAYMENT_EVIDENCE_REQUIRED', false),
            FILTER_VALIDATE_BOOL,
        ),
    ],

    'support' => [
        'default_priority' => env('V1_SUPPORT_DEFAULT_PRIORITY', 'normal'),
        'reply_sending_account_id' => env('V1_SUPPORT_SENDING_ACCOUNT_ID'),
        'inbound_webhook_secret' => env('V1_SUPPORT_INBOUND_WEBHOOK_SECRET'),
    ],
];
