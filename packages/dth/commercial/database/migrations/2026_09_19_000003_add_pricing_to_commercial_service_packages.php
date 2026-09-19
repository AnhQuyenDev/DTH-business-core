<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('commercial_service_packages')) {
            return;
        }

        if (! Schema::hasColumn('commercial_service_packages', 'price')) {
            Schema::table('commercial_service_packages', function (Blueprint $table): void {
                $table->decimal('price', 18, 2)->nullable();
            });
        }

        if (! Schema::hasColumn('commercial_service_packages', 'renewal_price')) {
            Schema::table('commercial_service_packages', function (Blueprint $table): void {
                $table->decimal('renewal_price', 18, 2)->nullable();
            });
        }

        if (! Schema::hasColumn('commercial_service_packages', 'setup_fee')) {
            Schema::table('commercial_service_packages', function (Blueprint $table): void {
                $table->decimal('setup_fee', 18, 2)->default(0);
            });
        }

        if (! Schema::hasColumn('commercial_service_packages', 'currency')) {
            Schema::table('commercial_service_packages', function (Blueprint $table): void {
                $table->string('currency', 3)->default('VND')->index();
            });
        }

        $this->backfillLegacyPricing();
    }

    private function backfillLegacyPricing(): void
    {
        if (! Schema::hasTable('service_packages') || ! Schema::hasColumn('service_packages', 'package_code')) {
            return;
        }

        $priceColumns = array_values(array_filter(
            ['price', 'renewal_price', 'setup_fee', 'currency'],
            fn (string $column): bool => Schema::hasColumn('service_packages', $column),
        ));

        if ($priceColumns === []) {
            return;
        }

        DB::table('service_packages')
            ->select(array_merge(['package_code'], $priceColumns))
            ->orderBy('package_code')
            ->get()
            ->each(function (object $row) use ($priceColumns): void {
                $data = [];
                foreach ($priceColumns as $column) {
                    $value = $row->{$column} ?? null;
                    if ($column === 'currency' && filled($value)) {
                        $value = strtoupper((string) $value);
                    }
                    $data[$column] = $value;
                }

                if ($data !== []) {
                    DB::table('commercial_service_packages')
                        ->where('package_code', (string) $row->package_code)
                        ->update($data);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('commercial_service_packages')) {
            return;
        }

        $columns = array_values(array_filter(
            ['price', 'renewal_price', 'setup_fee', 'currency'],
            fn (string $column): bool => Schema::hasColumn('commercial_service_packages', $column),
        ));

        if ($columns !== []) {
            Schema::table('commercial_service_packages', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
