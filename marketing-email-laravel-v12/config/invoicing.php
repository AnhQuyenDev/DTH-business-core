<?php

return [
    // V1 does not issue tax e-invoices. The provider contract is intentionally
    // present so MISA/Viettel/VNPT/etc. can be integrated later without
    // coupling payment verification to a specific vendor.
    'provider' => env('EINVOICE_PROVIDER', 'none'),
    'auto_issue_on_paid' => filter_var(env('EINVOICE_AUTO_ISSUE_ON_PAID', false), FILTER_VALIDATE_BOOL),
];
