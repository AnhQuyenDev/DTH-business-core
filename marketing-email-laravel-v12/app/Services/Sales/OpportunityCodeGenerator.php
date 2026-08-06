<?php

namespace App\Services\Sales;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class OpportunityCodeGenerator
{
    public function next(): string
    {
        $year = (int) now()->format('Y');

        DB::table('opportunity_code_sequences')->insertOrIgnore([
            'year' => $year,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $number = DB::transaction(function () use ($year): int {
            $row = DB::table('opportunity_code_sequences')
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                throw new RuntimeException(
                    'Không thể khởi tạo sequence mã Cơ hội kinh doanh.'
                );
            }

            $next = ((int) $row->last_number) + 1;

            DB::table('opportunity_code_sequences')
                ->where('year', $year)
                ->update([
                    'last_number' => $next,
                    'updated_at' => now(),
                ]);

            return $next;
        }, 5);

        return sprintf('OPP-%d-%06d', $year, $number);
    }
}
