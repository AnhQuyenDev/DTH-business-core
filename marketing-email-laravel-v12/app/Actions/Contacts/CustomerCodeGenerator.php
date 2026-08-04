<?php

namespace App\Actions\Contacts;

use App\Models\Crm\Customer;
use Illuminate\Support\Facades\DB;

final class CustomerCodeGenerator
{
    public function generate(): string
    {
        $prefix = 'CUS-' . now()->format('Ym') . '-';

        return DB::transaction(function () use ($prefix) {
            $last = Customer::withTrashed()
                ->where('customer_code', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderBy('customer_code', 'desc')
                ->value('customer_code');

            $nextNumber = $last ? (int) substr($last, -6) + 1 : 1;
            $nextNumber = min($nextNumber, 999999);

            return $prefix . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
        });
    }
}
