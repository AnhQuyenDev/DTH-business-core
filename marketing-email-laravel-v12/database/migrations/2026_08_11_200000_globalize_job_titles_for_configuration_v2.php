<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const TITLE_INDEX = 'positions_title_global_unique';
    private const CODE_INDEX = 'positions_code_global_unique';

    public function up(): void
    {
        if (! Schema::hasColumn('positions', 'code')) {
            Schema::table('positions', function (Blueprint $table): void {
                $table->string('code', 100)->nullable()->after('id');
            });
        }

        if (! Schema::hasColumn('positions', 'group_key')) {
            Schema::table('positions', function (Blueprint $table): void {
                $table->string('group_key', 40)->default('professional')->after('title')->index();
            });
        }

        if (! Schema::hasColumn('positions', 'function_key')) {
            Schema::table('positions', function (Blueprint $table): void {
                $table->string('function_key', 50)->nullable()->after('authority_level')->index();
            });
        }

        $this->normalizeAndMergeLegacyDepartmentTitles();
        $this->backfillMasterDataFields();

        // department_id is intentionally retained as a nullable compatibility
        // column for old imports/tests. New code never uses it to scope titles.
        if (Schema::hasColumn('positions', 'department_id')) {
            DB::table('positions')->update(['department_id' => null]);
        }

        Schema::table('positions', function (Blueprint $table): void {
            $table->unique('title', self::TITLE_INDEX);
            $table->unique('code', self::CODE_INDEX);
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table): void {
            $table->dropUnique(self::TITLE_INDEX);
            $table->dropUnique(self::CODE_INDEX);
        });

        if (Schema::hasColumn('positions', 'function_key')) {
            Schema::table('positions', function (Blueprint $table): void {
                $table->dropIndex(['function_key']);
                $table->dropColumn('function_key');
            });
        }

        if (Schema::hasColumn('positions', 'group_key')) {
            Schema::table('positions', function (Blueprint $table): void {
                $table->dropIndex(['group_key']);
                $table->dropColumn('group_key');
            });
        }

        if (Schema::hasColumn('positions', 'code')) {
            Schema::table('positions', function (Blueprint $table): void {
                $table->dropColumn('code');
            });
        }
    }

    private function normalizeAndMergeLegacyDepartmentTitles(): void
    {
        $rows = DB::table('positions')
            ->orderBy('id')
            ->get(['id', 'title', 'authority_level', 'is_active', 'sort_order']);

        $groups = [];

        foreach ($rows as $row) {
            $normalizedTitle = trim((string) $row->title);
            if ($normalizedTitle === '') {
                $normalizedTitle = 'Job Title '.$row->id;
            }

            // Use an accent-insensitive technical key so common MySQL
            // collations cannot reject the final unique index for titles that
            // differ only by case/diacritics/spacing.
            $key = Str::of($normalizedTitle)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim()->toString();
            $key = $key !== '' ? $key : 'job-title-'.$row->id;

            $groups[$key][] = $row;
        }

        foreach ($groups as $items) {
            $keep = array_shift($items);
            if (! $keep) {
                continue;
            }

            $authority = $this->highestAuthority(array_merge([$keep], $items));
            $active = collect(array_merge([$keep], $items))->contains(fn ($item): bool => (bool) $item->is_active);
            $sortOrder = (int) collect(array_merge([$keep], $items))->min('sort_order');

            DB::table('positions')->where('id', $keep->id)->update([
                'title' => trim((string) $keep->title),
                'authority_level' => $authority,
                'is_active' => $active,
                'sort_order' => $sortOrder,
                'updated_at' => now(),
            ]);

            if ($items === []) {
                continue;
            }

            $duplicateIds = collect($items)->pluck('id')->all();

            DB::table('staff')
                ->whereIn('position_id', $duplicateIds)
                ->update(['position_id' => $keep->id, 'updated_at' => now()]);

            DB::table('positions')->whereIn('id', $duplicateIds)->delete();
        }
    }

    private function backfillMasterDataFields(): void
    {
        $usedCodes = [];

        foreach (DB::table('positions')->orderBy('id')->get() as $row) {
            $title = trim((string) $row->title);
            $base = Str::of($title)->ascii()->slug('_')->lower()->toString();
            $base = $base !== '' ? $base : 'job_title_'.$row->id;

            $code = $base;
            $suffix = 2;
            while (isset($usedCodes[$code])) {
                $code = $base.'_'.$suffix++;
            }
            $usedCodes[$code] = true;

            DB::table('positions')->where('id', $row->id)->update([
                'code' => $code,
                'group_key' => $this->inferGroup($title),
                'function_key' => $this->inferFunction($title),
                'updated_at' => now(),
            ]);
        }
    }

    /** @param array<int, object> $items */
    private function highestAuthority(array $items): string
    {
        $rank = [
            'limited' => 10,
            'member' => 20,
            'lead' => 30,
            'manager' => 40,
            'executive' => 50,
        ];

        $best = 'member';
        foreach ($items as $item) {
            $candidate = (string) ($item->authority_level ?? 'member');
            if (($rank[$candidate] ?? 0) > ($rank[$best] ?? 0)) {
                $best = $candidate;
            }
        }

        return $best;
    }

    private function inferGroup(string $title): string
    {
        $value = $this->lower($title);

        return match (true) {
            str_contains($value, 'giám đốc'), str_contains($value, 'director') => 'leadership',
            str_contains($value, 'trưởng phòng'), str_contains($value, 'phó phòng'),
            str_contains($value, 'manager'), str_contains($value, 'head') => 'management',
            str_contains($value, 'thực tập'), str_contains($value, 'cộng tác'),
            str_contains($value, 'intern'), str_contains($value, 'collaborator') => 'temporary',
            str_contains($value, 'nhân viên'), str_contains($value, 'staff'),
            str_contains($value, 'operator') => 'operations',
            default => 'professional',
        };
    }

    private function inferFunction(string $title): ?string
    {
        $value = $this->lower($title);

        return match (true) {
            str_contains($value, 'marketing') => 'marketing',
            str_contains($value, 'kinh doanh'), str_contains($value, 'sales'), str_contains($value, 'account manager') => 'sales',
            str_contains($value, 'chăm sóc khách hàng'), str_contains($value, 'cskh'), str_contains($value, 'customer service') => 'customer_service',
            str_contains($value, 'kế toán'), str_contains($value, 'tài chính'), str_contains($value, 'finance'), str_contains($value, 'accounting') => 'finance',
            str_contains($value, 'kỹ thuật'), str_contains($value, 'technical'), str_contains($value, 'developer'), str_contains($value, 'engineer') => 'technical',
            default => null,
        };
    }

    private function lower(string $value): string
    {
        $value = trim($value);

        return function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);
    }
};
