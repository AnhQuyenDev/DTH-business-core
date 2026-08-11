<?php

namespace App\Contracts\Billing;

use App\Models\Finance\Payment;

interface ElectronicInvoiceProvider
{
    /**
     * Provider adapters return null when no invoice is issued.
     * Future integrations can return provider/reference/number/url metadata.
     *
     * @return array{provider?:string,external_id?:string,number?:string,url?:string,issued_at?:string}|null
     */
    public function issueForPayment(Payment $payment): ?array;
}
