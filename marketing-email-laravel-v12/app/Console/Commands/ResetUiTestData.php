<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class ResetUiTestData extends Command
{
    protected $signature = 'crm:reset-ui-test-data
        {--dry-run : Chỉ thống kê, không xóa dữ liệu}
        {--force : Xác nhận xóa dữ liệu test}
        {--email=* : Email test cần xóa; có thể truyền nhiều lần}
        {--batch=* : Mã đợt test trong workbook, ví dụ UITEST-HOSTING-01}
        {--tax-code=* : Mã số thuế Company test cần xóa; có thể truyền nhiều lần}
        {--reset-sequences : Đưa sequence Lead/Company/Opportunity về 0 nếu bảng nghiệp vụ tương ứng đã trống}';

    protected $description =
        'Xóa sạch dữ liệu nghiệp vụ của bộ UI test mà không xóa Campaign, Template, Landing Page, User, Staff hoặc cấu hình';

    /** @var list<string> */
    private const DEFAULT_EMAILS = [
        'lead.hosting.b2b.001@example.com',
        'lead.hosting.personal.001@example.com',
        'lead.hosting.b2b.002@example.com',
    ];

    /** @var list<string> */
    private const DEFAULT_TAX_CODES = [
        '9999999999',
    ];

    public function handle(): int
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'Từ chối thực hiện: APP_ENV đang là production.'
            );
        }

        $batches = collect($this->option('batch'))
            ->filter()
            ->map(fn (string $batch): string => trim($batch))
            ->unique()
            ->values();

        $emails = collect($this->option('email'))
            ->filter()
            ->map(fn (string $email): string => mb_strtolower(trim($email)))
            ->merge($this->discoverEmailsByBatch($batches))
            ->unique()
            ->values();

        if ($emails->isEmpty() && $batches->isEmpty()) {
            $emails = collect(self::DEFAULT_EMAILS);
        }

        $taxCodes = collect($this->option('tax-code'))
            ->filter()
            ->whenEmpty(fn (Collection $items) => $items->push(...self::DEFAULT_TAX_CODES))
            ->map(fn (string $value): string => trim($value))
            ->unique()
            ->values();

        $ids = $this->resolveIds($emails, $taxCodes);
        $summary = $this->buildSummary($ids, $emails, $taxCodes);

        $this->newLine();
        $this->info('PHẠM VI DỮ LIỆU UI TEST SẼ XỬ LÝ');
        $this->table(['Nhóm', 'Số lượng'], collect($summary)
            ->map(fn (int $count, string $label): array => [$label, $count])
            ->values()
            ->all());

        $this->line('Batch: '.($batches->isEmpty() ? '(không chỉ định)' : $batches->implode(', ')));
        $this->line('Email: '.($emails->isEmpty() ? '(không tìm thấy)' : $emails->implode(', ')));
        $this->line('MST: '.$taxCodes->implode(', '));

        if ((bool) $this->option('dry-run') || ! (bool) $this->option('force')) {
            $this->warn('DRY RUN: chưa có dữ liệu nào bị xóa.');
            $this->line('Xóa thật bằng lệnh:');
            $this->line('php artisan crm:reset-ui-test-data --force --reset-sequences');
            $this->line('Hoặc theo batch: php artisan crm:reset-ui-test-data --batch=UITEST-HOSTING-01 --force');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($ids, $emails, $taxCodes): void {
            $this->deleteOperationalData($ids, $emails, $taxCodes);
        }, 5);

        if ((bool) $this->option('reset-sequences')) {
            $this->resetSequencesWhenSafe();
        }

        $this->newLine();
        $this->info('Đã xóa sạch dữ liệu nghiệp vụ của bộ UI test.');
        $this->line('Campaign, Ads Campaign, Template, Landing Page, User, Staff, Sending Account và cấu hình được giữ nguyên.');

        return self::SUCCESS;
    }

    private function discoverEmailsByBatch(Collection $batches): Collection
    {
        if ($batches->isEmpty()) {
            return collect();
        }

        $needles = $batches->map(
            fn (string $batch): string => '+dth.'.mb_strtolower(
                str_replace('-', '.', $batch)
            ).'.'
        );
        $emails = collect();

        foreach ([
            ['personal_contact_profiles', 'email'],
            ['business_contact_profiles', 'business_email'],
            ['landing_page_submissions', 'normalized_email'],
            ['customers', 'normalized_email'],
            ['suppression_entries', 'email'],
        ] as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $query = DB::table($table)->whereNotNull($column);
            $query->where(function ($nested) use ($column, $needles): void {
                foreach ($needles as $index => $needle) {
                    $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                    $nested->{$method}(
                        'LOWER('.$column.') LIKE ?',
                        ['%'.str_replace(['%', '_'], ['\%', '\_'], $needle).'%']
                    );
                }
            });

            $emails = $emails->merge(
                $query->pluck($column)
                    ->filter()
                    ->map(fn ($email): string => mb_strtolower(trim((string) $email)))
            );
        }

        return $emails->unique()->values();
    }

    /**
     * @return array<string, Collection<int, int>>
     */
    private function resolveIds(Collection $emails, Collection $taxCodes): array
    {
        $contactIds = collect();

        if (Schema::hasTable('personal_contact_profiles')) {
            $contactIds = $contactIds->merge(
                DB::table('personal_contact_profiles')
                    ->whereIn(DB::raw('LOWER(email)'), $emails->all())
                    ->pluck('contact_id')
            );
        }

        if (Schema::hasTable('business_contact_profiles')) {
            $contactIds = $contactIds->merge(
                DB::table('business_contact_profiles')
                    ->whereIn(DB::raw('LOWER(business_email)'), $emails->all())
                    ->pluck('contact_id')
            );
        }

        $submissionIds = collect();
        if (Schema::hasTable('landing_page_submissions')) {
            $submissionQuery = DB::table('landing_page_submissions');

            if (Schema::hasColumn('landing_page_submissions', 'normalized_email')) {
                $submissionQuery->whereIn(
                    DB::raw('LOWER(normalized_email)'),
                    $emails->all()
                );
            }

            if ($contactIds->isNotEmpty() && Schema::hasColumn('landing_page_submissions', 'contact_id')) {
                $submissionQuery->orWhereIn('contact_id', $contactIds->all());
            }

            $submissionIds = $submissionQuery->pluck('id');

            if (Schema::hasColumn('landing_page_submissions', 'contact_id')) {
                $contactIds = $contactIds->merge(
                    DB::table('landing_page_submissions')
                        ->whereIn('id', $submissionIds->all())
                        ->whereNotNull('contact_id')
                        ->pluck('contact_id')
                );
            }
        }

        $contactIds = $contactIds->filter()->map(fn ($id): int => (int) $id)->unique()->values();
        $submissionIds = $submissionIds->filter()->map(fn ($id): int => (int) $id)->unique()->values();

        $leadIds = collect();
        if (Schema::hasTable('leads')) {
            $query = DB::table('leads');
            $hasCondition = false;

            if ($contactIds->isNotEmpty()) {
                $query->whereIn('contact_id', $contactIds->all());
                $hasCondition = true;
            }

            if ($submissionIds->isNotEmpty()) {
                $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                $query->{$method}('submission_id', $submissionIds->all());
                $hasCondition = true;
            }

            if ($hasCondition) {
                $leadIds = $query->pluck('id');
            }
        }

        $companyIds = collect();
        if (Schema::hasTable('companies')) {
            $companyIds = $companyIds->merge(
                DB::table('companies')
                    ->whereIn('tax_code', $taxCodes->all())
                    ->pluck('id')
            );
        }

        if (Schema::hasTable('business_contact_profiles') && $contactIds->isNotEmpty()) {
            $companyIds = $companyIds->merge(
                DB::table('business_contact_profiles')
                    ->whereIn('contact_id', $contactIds->all())
                    ->whereNotNull('company_id')
                    ->pluck('company_id')
            );
        }

        if (Schema::hasTable('leads') && $leadIds->isNotEmpty()) {
            $companyIds = $companyIds->merge(
                DB::table('leads')
                    ->whereIn('id', $leadIds->all())
                    ->whereNotNull('company_id')
                    ->pluck('company_id')
            );
        }

        if (Schema::hasTable('landing_page_submissions') && $submissionIds->isNotEmpty()) {
            $companyIds = $companyIds->merge(
                DB::table('landing_page_submissions')
                    ->whereIn('id', $submissionIds->all())
                    ->whereNotNull('company_id')
                    ->pluck('company_id')
            );
        }

        $companyIds = $companyIds->filter()->map(fn ($id): int => (int) $id)->unique()->values();
        $leadIds = $leadIds->filter()->map(fn ($id): int => (int) $id)->unique()->values();

        $opportunityIds = collect();
        if (Schema::hasTable('sales_opportunities')) {
            $query = DB::table('sales_opportunities');
            $hasCondition = false;

            foreach ([
                'lead_id' => $leadIds,
                'primary_contact_id' => $contactIds,
                'company_id' => $companyIds,
            ] as $column => $values) {
                if ($values->isEmpty() || ! Schema::hasColumn('sales_opportunities', $column)) {
                    continue;
                }

                $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                $query->{$method}($column, $values->all());
                $hasCondition = true;
            }

            if ($hasCondition) {
                $opportunityIds = $query->pluck('id');
            }
        }

        $customerIds = collect();
        if (Schema::hasTable('customers')) {
            $query = DB::table('customers');
            $hasCondition = false;

            foreach ([
                'contact_id' => $contactIds,
                'company_id' => $companyIds,
                'converted_from_opportunity_id' => $opportunityIds,
            ] as $column => $values) {
                if ($values->isEmpty() || ! Schema::hasColumn('customers', $column)) {
                    continue;
                }

                $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                $query->{$method}($column, $values->all());
                $hasCondition = true;
            }

            if ($hasCondition) {
                $customerIds = $query->pluck('id');
            }
        }

        $quotationIds = collect();
        if (Schema::hasTable('quotations')) {
            $query = DB::table('quotations');
            $hasCondition = false;

            foreach ([
                'opportunity_id' => $opportunityIds,
                'customer_id' => $customerIds,
                'contact_id' => $contactIds,
                'company_id' => $companyIds,
            ] as $column => $values) {
                if ($values->isEmpty() || ! Schema::hasColumn('quotations', $column)) {
                    continue;
                }

                $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                $query->{$method}($column, $values->all());
                $hasCondition = true;
            }

            if ($hasCondition) {
                $quotationIds = $query->pluck('id');
            }
        }

        return [
            'contacts' => $contactIds,
            'submissions' => $submissionIds,
            'leads' => $leadIds,
            'companies' => $companyIds,
            'opportunities' => $opportunityIds->filter()->map(fn ($id): int => (int) $id)->unique()->values(),
            'customers' => $customerIds->filter()->map(fn ($id): int => (int) $id)->unique()->values(),
            'quotations' => $quotationIds->filter()->map(fn ($id): int => (int) $id)->unique()->values(),
        ];
    }

    /** @return array<string, int> */
    private function buildSummary(array $ids, Collection $emails, Collection $taxCodes): array
    {
        return [
            'Lượt gửi biểu mẫu' => $ids['submissions']->count(),
            'Contact' => $ids['contacts']->count(),
            'Lead' => $ids['leads']->count(),
            'Company' => $ids['companies']->count(),
            'Opportunity' => $ids['opportunities']->count(),
            'Quotation' => $ids['quotations']->count(),
            'Customer' => $ids['customers']->count(),
            'Email test nhận diện' => $emails->count(),
            'MST test nhận diện' => $taxCodes->count(),
        ];
    }

    private function deleteOperationalData(array $ids, Collection $emails, Collection $taxCodes): void
    {
        $quotationIds = $ids['quotations'];
        $opportunityIds = $ids['opportunities'];
        $customerIds = $ids['customers'];
        $leadIds = $ids['leads'];
        $submissionIds = $ids['submissions'];
        $contactIds = $ids['contacts'];
        $companyIds = $ids['companies'];

        foreach ([
            'quotation_approvals',
            'quotation_confirmations',
            'quotation_documents',
            'quotation_email_logs',
            'quotation_items',
        ] as $table) {
            $this->deleteWhereIn($table, 'quotation_id', $quotationIds);
        }
        $this->deleteWhereIn('quotations', 'id', $quotationIds);

        $this->deleteWhereIn('opportunity_interactions', 'opportunity_id', $opportunityIds);
        $this->deleteWhereIn('opportunity_contacts', 'opportunity_id', $opportunityIds);
        $this->deleteWhereIn('sales_opportunities', 'id', $opportunityIds);

        $this->deleteWhereIn('customer_interactions', 'customer_id', $customerIds);
        $this->deleteWhereIn('customer_distribution_items', 'customer_id', $customerIds);
        $this->deleteWhereIn('customer_assignments', 'customer_id', $customerIds);
        $this->deleteWhereIn('customer_list_members', 'customer_id', $customerIds);
        $this->deleteWhereIn('customer_tag', 'customer_id', $customerIds);
        $this->deleteWhereIn('customers', 'id', $customerIds);
        $this->deleteEmptyDistributionBatches();

        $recipientIds = collect();
        if (Schema::hasTable('campaign_recipients') && $contactIds->isNotEmpty()) {
            $recipientIds = DB::table('campaign_recipients')
                ->whereIn('contact_id', $contactIds->all())
                ->pluck('id');
        }
        $this->deleteWhereIn('tracked_links', 'campaign_recipient_id', $recipientIds);
        $this->deleteWhereIn('email_events', 'campaign_recipient_id', $recipientIds);
        $this->deleteWhereIn('email_events', 'contact_id', $contactIds);
        $this->deleteWhereIn('email_events', 'customer_id', $customerIds);
        $this->deleteWhereIn('campaign_recipients', 'contact_id', $contactIds);
        $this->deleteWhereIn('campaign_recipients', 'customer_id', $customerIds);
        $this->deleteWhereIn('suppression_entries', 'contact_id', $contactIds);
        $this->deleteWhereIn('suppression_entries', 'customer_id', $customerIds);
        $this->deleteWhereIn('suppression_entries', 'email', $emails);

        $qualificationIds = collect();
        if (Schema::hasTable('contact_qualifications')) {
            $query = DB::table('contact_qualifications');
            $hasCondition = false;

            if ($leadIds->isNotEmpty() && Schema::hasColumn('contact_qualifications', 'lead_id')) {
                $query->whereIn('lead_id', $leadIds->all());
                $hasCondition = true;
            }
            if ($contactIds->isNotEmpty()) {
                $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                $query->{$method}('contact_id', $contactIds->all());
                $hasCondition = true;
            }
            if ($hasCondition) {
                $qualificationIds = $query->pluck('id');
            }
        }

        $this->deleteWhereIn('contact_qualification_notes', 'contact_qualification_id', $qualificationIds);
        $this->deleteWhereIn('contact_qualifications', 'id', $qualificationIds);
        $this->deleteWhereIn('lead_activities', 'lead_id', $leadIds);
        $this->deleteWhereIn('leads', 'id', $leadIds);

        $this->deleteWhereIn('company_match_candidates', 'submission_id', $submissionIds);
        $this->deleteWhereIn('company_match_candidates', 'contact_id', $contactIds);
        $this->deleteWhereIn('company_match_candidates', 'suggested_company_id', $companyIds);
        $this->deleteWhereIn('company_assignments', 'company_id', $companyIds);
        $this->deleteWhereIn('company_contacts', 'contact_id', $contactIds);

        $this->deleteWhereIn('landing_page_submissions', 'id', $submissionIds);

        $this->deleteWhereIn('contact_custom_field_values', 'contact_id', $contactIds);
        $this->deleteWhereIn('contact_list_members', 'contact_id', $contactIds);
        $this->deleteWhereIn('contact_tag', 'contact_id', $contactIds);
        $this->deleteWhereIn('personal_contact_profiles', 'contact_id', $contactIds);
        $this->deleteWhereIn('business_contact_profiles', 'contact_id', $contactIds);
        $this->deleteWhereIn('contacts', 'id', $contactIds);

        $this->deleteSafeTestCompanies($companyIds, $taxCodes);
        $this->deleteAuditLogs($ids);
    }

    private function deleteSafeTestCompanies(Collection $companyIds, Collection $taxCodes): void
    {
        if ($companyIds->isEmpty() || ! Schema::hasTable('companies')) {
            return;
        }

        $deletableIds = DB::table('companies')
            ->whereIn('id', $companyIds->all())
            ->whereIn('tax_code', $taxCodes->all())
            ->pluck('id')
            ->filter(function ($companyId): bool {
                foreach ([
                    ['company_contacts', 'company_id'],
                    ['leads', 'company_id'],
                    ['sales_opportunities', 'company_id'],
                    ['customers', 'company_id'],
                    ['quotations', 'company_id'],
                ] as [$table, $column]) {
                    if (
                        Schema::hasTable($table)
                        && Schema::hasColumn($table, $column)
                        && DB::table($table)->where($column, $companyId)->exists()
                    ) {
                        return false;
                    }
                }

                return true;
            })
            ->values();

        $this->deleteWhereIn('companies', 'id', $deletableIds);
    }

    private function deleteAuditLogs(array $ids): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        $types = [
            'contacts' => [
                'App\\Models\\Marketing\\Contact',
            ],
            'submissions' => [
                'App\\Models\\Marketing\\LandingPageSubmission',
            ],
            'leads' => [
                'App\\Models\\Crm\\Lead',
            ],
            'companies' => [
                'App\\Models\\Crm\\Company',
            ],
            'opportunities' => [
                'App\\Models\\Sales\\Opportunity',
            ],
            'quotations' => [
                'App\\Models\\Sales\\Quotation',
            ],
            'customers' => [
                'App\\Models\\Crm\\Customer',
            ],
        ];

        foreach ($types as $key => $modelTypes) {
            if ($ids[$key]->isEmpty()) {
                continue;
            }

            DB::table('audit_logs')
                ->whereIn('auditable_type', $modelTypes)
                ->whereIn('auditable_id', $ids[$key]->all())
                ->delete();
        }
    }

    private function deleteEmptyDistributionBatches(): void
    {
        if (
            ! Schema::hasTable('customer_distribution_batches')
            || ! Schema::hasTable('customer_distribution_items')
        ) {
            return;
        }

        DB::table('customer_distribution_batches')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('customer_distribution_items')
                    ->whereColumn(
                        'customer_distribution_items.distribution_batch_id',
                        'customer_distribution_batches.id'
                    );
            })
            ->delete();
    }

    private function resetSequencesWhenSafe(): void
    {
        $mapping = [
            'leads' => 'lead_code_sequences',
            'companies' => 'company_code_sequences',
            'sales_opportunities' => 'opportunity_code_sequences',
        ];

        foreach ($mapping as $entityTable => $sequenceTable) {
            if (
                Schema::hasTable($entityTable)
                && Schema::hasTable($sequenceTable)
                && DB::table($entityTable)->count() === 0
            ) {
                DB::table($sequenceTable)->delete();
                $this->line("Đã reset {$sequenceTable} vì {$entityTable} đang trống.");
            }
        }
    }

    private function deleteWhereIn(string $table, string $column, Collection $values): int
    {
        if (
            $values->isEmpty()
            || ! Schema::hasTable($table)
            || ! Schema::hasColumn($table, $column)
        ) {
            return 0;
        }

        return DB::table($table)
            ->whereIn($column, $values->all())
            ->delete();
    }
}
