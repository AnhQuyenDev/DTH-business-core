<?php

namespace App\Console\Commands\Crm;

use App\Enums\Crm\CompanyLifecycleStage;
use App\Models\Crm\BusinessContactProfile;
use App\Models\Crm\Company;
use App\Services\Crm\CompanyCodeGenerator;
use App\Services\Crm\CompanyContactLinkService;
use App\Services\Crm\CompanyNormalizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillCompaniesFromBusinessProfiles extends Command
{
    protected $signature = 'crm:backfill-companies
        {--dry-run : Chỉ báo cáo, không ghi dữ liệu}
        {--chunk=200 : Số profile xử lý mỗi chunk}';

    protected $description =
        'Backfill Company từ business_contact_profiles legacy';

    public function handle(
        CompanyNormalizationService $normalizer,
        CompanyCodeGenerator $codeGenerator,
        CompanyContactLinkService $linkService,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(1, (int) $this->option('chunk'));

        $stats = [
            'would_create' => 0,
            'matched_tax' => 0,
            'grouped_name' => 0,
            'would_attach' => 0,
            'would_update_profile' => 0,
            'conflict' => 0,
            'skipped' => 0,
            'created' => 0,
            'attached' => 0,
            'updated_profile' => 0,
        ];

        /*
         * Dùng để mô phỏng nhóm trong dry-run mà không ghi DB.
         */
        $simulatedKeys = [];

        BusinessContactProfile::query()
            ->whereNull('company_id')
            ->where(function ($query): void {
                $query
                    ->whereNotNull('company_name')
                    ->orWhereNotNull('tax_code');
            })
            ->orderBy('id')
            ->chunkById(
                $chunkSize,
                function ($profiles) use (
                    $dryRun,
                    $normalizer,
                    $codeGenerator,
                    $linkService,
                    &$stats,
                    &$simulatedKeys,
                ): void {
                    foreach ($profiles as $profile) {
                        $taxCode = $normalizer->normalizeTaxCode(
                            $profile->tax_code
                        );
                        $normalizedName = $normalizer->normalizeName(
                            $profile->company_name
                        );

                        if ($taxCode === null && $normalizedName === null) {
                            $stats['skipped']++;

                            continue;
                        }

                        $groupKey = $taxCode !== null
                            ? 'tax:'.$taxCode
                            : 'name:'.$normalizedName;

                        $company = $taxCode !== null
                            ? Company::query()
                                ->where('tax_code', $taxCode)
                                ->first()
                            : Company::query()
                                ->whereNull('tax_code')
                                ->where('normalized_name', $normalizedName)
                                ->first();

                        if ($dryRun) {
                            if ($company !== null) {
                                if ($taxCode !== null) {
                                    $stats['matched_tax']++;
                                } else {
                                    $stats['grouped_name']++;
                                }
                            } elseif (isset($simulatedKeys[$groupKey])) {
                                if ($taxCode !== null) {
                                    $stats['matched_tax']++;
                                } else {
                                    $stats['grouped_name']++;
                                }
                            } else {
                                $simulatedKeys[$groupKey] = true;
                                $stats['would_create']++;
                            }

                            $stats['would_attach']++;
                            $stats['would_update_profile']++;

                            continue;
                        }

                        DB::transaction(function () use (
                            $profile,
                            $taxCode,
                            $normalizedName,
                            $normalizer,
                            $codeGenerator,
                            $linkService,
                            &$stats,
                        ): void {
                            $company = $taxCode !== null
                                ? Company::query()
                                    ->where('tax_code', $taxCode)
                                    ->lockForUpdate()
                                    ->first()
                                : Company::query()
                                    ->whereNull('tax_code')
                                    ->where(
                                        'normalized_name',
                                        $normalizedName
                                    )
                                    ->lockForUpdate()
                                    ->first();

                            if ($company === null) {
                                $company = Company::query()->create([
                                    'company_code' => $codeGenerator->next(),
                                    'legal_name' => filled($profile->company_name)
                                        ? trim($profile->company_name)
                                        : 'Công ty MST '.$taxCode,
                                    'normalized_name' => $normalizedName
                                        ?? ('mst '.$taxCode),
                                    'tax_code' => $taxCode,
                                    'email_domain' => $normalizer
                                        ->extractBusinessDomain(
                                            $profile->business_email
                                        ),
                                    'phone' => $profile->business_phone,
                                    'normalized_phone' => $normalizer
                                        ->normalizePhone(
                                            $profile->business_phone
                                        ),
                                    'industry' => $profile->industry,
                                    'address' => $profile->company_address,
                                    'province' => $profile->province,
                                    'lifecycle_stage' => CompanyLifecycleStage::Prospect->value,
                                ]);

                                $stats['created']++;
                            } elseif ($taxCode !== null) {
                                $stats['matched_tax']++;
                            } else {
                                $stats['grouped_name']++;
                            }

                            $linkService->link(
                                company: $company,
                                contact: $profile->contact,
                                profile: $profile,
                                submission: null,
                                jobTitle: $profile->contact_position,
                            );

                            $stats['attached']++;
                            $stats['updated_profile']++;
                        }, 3);
                    }
                }
            );

        $this->newLine();
        $this->table(
            ['Chỉ số', 'Số lượng'],
            collect($stats)
                ->map(fn (int $value, string $key): array => [$key, $value])
                ->values()
                ->all()
        );

        if ($dryRun) {
            $this->warn('DRY-RUN: không có dữ liệu nào được ghi.');
        } else {
            $this->info('Backfill Company hoàn tất.');
        }

        return self::SUCCESS;
    }
}
