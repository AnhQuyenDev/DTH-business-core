<?php

namespace App\Contracts\Tax;

use App\DTO\Tax\TaxVerificationResult;

interface TaxCodeVerificationProvider
{
    public function verify(string $taxCode): TaxVerificationResult;
}
