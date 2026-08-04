<?php

namespace App\Services\Sales;

use Illuminate\Support\Facades\DB;

class QuotationCodeGenerator
{
    public function generate(): string
    {
        $year = now()->format('Y');

        $lastCode = DB::table('quotations')
            ->where('quotation_code', 'like', "QT{$year}%")
            ->orderBy('quotation_code', 'desc')
            ->lockForUpdate()
            ->value('quotation_code');

        if ($lastCode) {
            $lastNumber = (int) substr($lastCode, 6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('QT%s%05d', $year, $newNumber);
    }

    public function generatePublicToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
