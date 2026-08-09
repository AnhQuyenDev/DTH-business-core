<?php

return [
    'quotation' => [
        // Internal notification recipients. Leave blank to disable a specific
        // notification. These messages are delivered through the quotation's
        // Sending Account, not through MAIL_MAILER=log.
        'accepted_notification_email' => env('SALES_QUOTATION_ACCEPTED_NOTIFICATION_EMAIL'),
        'rejected_notification_email' => env('SALES_QUOTATION_REJECTED_NOTIFICATION_EMAIL'),
        'revision_notification_email' => env('SALES_QUOTATION_REVISION_NOTIFICATION_EMAIL'),
        'expired_notification_email' => env('SALES_QUOTATION_EXPIRED_NOTIFICATION_EMAIL'),
        'expiring_notification_email' => env('SALES_QUOTATION_EXPIRING_NOTIFICATION_EMAIL'),
    ],
];
