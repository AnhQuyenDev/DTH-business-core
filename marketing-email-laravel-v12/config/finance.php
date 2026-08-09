<?php

return [
    // Evidence uploaded by the customer is private business documentation.
    // Keep it off the public disk and expose it only through authorized routes.
    'evidence_disk' => env('PAYMENT_EVIDENCE_DISK', 'local'),
    'evidence_max_files' => (int) env('PAYMENT_EVIDENCE_MAX_FILES', 3),
    'evidence_max_kb' => (int) env('PAYMENT_EVIDENCE_MAX_KB', 10240),
    'evidence_mimes' => ['jpg', 'jpeg', 'png', 'webp', 'pdf'],

    // Company-issued receipts are immutable private snapshots.
    'receipt_disk' => env('PAYMENT_RECEIPT_DISK', 'local'),
];
