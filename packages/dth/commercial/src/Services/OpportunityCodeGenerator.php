<?php

namespace Dth\Commercial\Services;

use Dth\Commercial\Models\Opportunity;
use Illuminate\Support\Facades\DB;

final class OpportunityCodeGenerator
{
    public function next(): string
    {
        $year = now()->format('Y');

        return DB::transaction(function () use ($year): string {
            $prefix = 'OPP-'.$year.'-';
            $last = Opportunity::withTrashed()
                ->where('opportunity_code', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('opportunity_code')
                ->value('opportunity_code');

            $number = $last ? ((int) substr((string) $last, -6)) + 1 : 1;

            return $prefix.str_pad((string) $number, 6, '0', STR_PAD_LEFT);
        });
    }
}
