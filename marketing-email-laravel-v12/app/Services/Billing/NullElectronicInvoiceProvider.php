<?php

namespace App\Services\Billing;

use App\Contracts\Billing\ElectronicInvoiceProvider;
use App\Models\Finance\Payment;

final class NullElectronicInvoiceProvider implements ElectronicInvoiceProvider
{
    public function issueForPayment(Payment $payment): ?array
    {
        return null;
    }
}
