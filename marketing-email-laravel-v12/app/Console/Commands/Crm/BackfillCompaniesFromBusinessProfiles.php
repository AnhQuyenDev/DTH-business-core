<?php

namespace App\Console\Commands\Crm;

use App\Enums\Crm\CompanyLifecycleStage;
use App\Models\Crm\BusinessContactProfile;
use App\Models\Crm\Company;
use App\Services\Crm\CompanyCodeGenerator;
use App\Services\Crm\CompanyResolutionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillCompaniesFromBusinessProfiles extends Command
{
    protected $signature = 'crm:backfill-companies {--dry-run : Chỉ báo cáo, không ghi dữ liệu}';

    protected $description = 'Backfill companies from legacy business_contact_profiles';

    public function handle(CompanyResolutionService $resolution, CompanyCodeGenerator $codeGenerator): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $profiles = BusinessContactProfile::query()
            ->whereNull('company_id')
            ->where(fn ($q) => $q
                ->whereNotNull('company_name')
                ->orWhereNotNull('tax_code'))
            ->orderBy('id')
            ->get();

        if ($profiles->isEmpty()) {
            $this->info('Không có profile cần backfill.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Tìm thấy %d profile cần backfill.', $profiles->count()));

        $created = 0;
        $matched = 0;
        $skipped = 0;

        foreach ($profiles as $profile) {
            $data = [
                'tax_code' => $profile->tax_code,
                'business_email' => $profile->business_email,
                'company_name' => $profile->company_name,
            ];

            $result = $resolution->resolve($data);
            $company = null;

            if ($result->found() && $result->isAutoMatch()) {
                $company = $result->company;
                $matched++;
            }

            if ($company === null && ! $dryRun) {
                $company = DB::transaction(function () use ($profile, $resolution, $codeGenerator) {
                    $company = Company::query()->create([
                        'company_code' => $codeGenerator->next(),
                        'legal_name' => $profile->company_name ?? ('Công ty MST '.$profile->tax_code),
                        'normalized_name' => $resolution->normalizeName($profile->company_name)
                            ?? $profile->company_name,
                        'tax_code' => $profile->tax_code,
                        'email_domain' => $this->extractDomain($profile->business_email),
                        'phone' => $profile->business_phone,
                        'normalized_phone' => $profile->business_phone
                            ? preg_replace('/[^0-9]/', '', $profile->business_phone)
                            : null,
                        'industry' => $profile->industry,
                        'address' => $profile->company_address,
                        'province' => $profile->province,
                        'lifecycle_stage' => CompanyLifecycleStage::Prospect->value,
                    ]);

                    $company->contacts()->syncWithoutDetaching([
                        $profile->contact_id => [
                            'job_title' => $profile->contact_position,
                            'is_primary' => true,
                            'is_active' => true,
                        ],
                    ]);

                    $profile->update(['company_id' => $company->id]);

                    return $company;
                });
                $created++;
            } elseif ($company !== null && ! $dryRun) {
                DB::transaction(function () use ($company, $profile) {
                    $company->contacts()->syncWithoutDetaching([
                        $profile->contact_id => [
                            'job_title' => $profile->contact_position,
                            'is_primary' => ! $company->contacts()->exists(),
                            'is_active' => true,
                        ],
                    ]);
                    $profile->update(['company_id' => $company->id]);
                });
            } else {
                $skipped++;
            }
        }

        $this->info(sprintf('Tạo mới: %d | Match: %d | Bỏ qua: %d', $created, $matched, $skipped));

        return self::SUCCESS;
    }

    private function extractDomain(?string $email): ?string
    {
        if (blank($email) || ! str_contains($email, '@')) {
            return null;
        }

        $domain = strtolower(trim(substr($email, strrpos($email, '@') + 1)));

        return in_array($domain, ['gmail.com', 'outlook.com', 'hotmail.com', 'yahoo.com', 'icloud.com'], true)
            ? null
            : $domain;
    }
}
