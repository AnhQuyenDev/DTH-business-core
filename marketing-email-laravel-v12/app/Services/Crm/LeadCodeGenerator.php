<?php

namespace App\Services\Crm;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class LeadCodeGenerator
{
    public function next(): string
    {
        $year = (int) now()->format('Y');

        DB::table('lead_code_sequences')->insertOrIgnore([
            'year' => $year,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $number = DB::transaction(function () use ($year): int {
            $row = DB::table('lead_code_sequences')
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                throw new RuntimeException(
                    'Không thể khởi tạo sequence mã Lead.'
                );
            }

            $next = ((int) $row->last_number) + 1;

            DB::table('lead_code_sequences')
                ->where('year', $year)
                ->update([
                    'last_number' => $next,
                    'updated_at' => now(),
                ]);

            return $next;
        }, 5);

        return sprintf('LEAD-%d-%06d', $year, $number);
    }
}
