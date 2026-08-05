<?php

namespace App\Console\Commands\Crm;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanCrmMarketingData extends Command
{
    protected $signature = 'crm:clean-data
        {--dry-run : Chỉ liệt kê, không xoá dữ liệu}';

    protected $description =
        'Xoá toàn bộ dữ liệu giao dịch CRM + Marketing, giữ lại '
        .'landing page, campaign, form template và cấu hình';

    protected array $tables = [
        // --- Marketing: dữ liệu giao dịch ---
        'contact_tag',
        'contact_list_members',
        'contact_custom_field_values',
        'contact_lists',
        'suppression_entries',
        'tracked_links',
        'email_events',
        'campaign_recipients',
        'landing_page_submissions',
        'import_batches',
        // --- CRM: dữ liệu giao dịch ---
        'company_contacts',
        'company_match_candidates',
        'company_assignments',
        'companies',
        'business_contact_profiles',
        'personal_contact_profiles',
        'contact_qualification_notes',
        'contact_qualifications',
        'customer_distribution_items',
        'customer_distribution_batches',
        'customer_assignments',
        'customer_interactions',
        'customer_tag',
        'customer_list_members',
        'customer_lists',
        'customers',
        'contacts',
        'tags',
        'segments',
        // --- Bộ đếm & log ---
        'company_code_sequences',
        'audit_logs',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun) {
            $this->warn('Thao tác này sẽ XOÁ VĨNH VIỄN dữ liệu giao dịch CRM + Marketing.');
            $this->warn('Lưu ý: xoá customers sẽ cascade xoá các quotation/sales liên quan.');
            if (! $this->confirm('Bạn có chắc chắn muốn tiếp tục?')) {
                return self::FAILURE;
            }
        }

        $existing = collect($this->tables)
            ->filter(fn (string $table): bool => Schema::hasTable($table));

        if ($existing->isEmpty()) {
            $this->info('Không có bảng dữ liệu nào cần làm sạch.');

            return self::SUCCESS;
        }

        $driver = DB::connection()->getDriverName();

        DB::statement($driver === 'sqlite'
            ? 'PRAGMA foreign_keys = OFF'
            : 'SET FOREIGN_KEY_CHECKS = 0');

        try {
            foreach ($existing as $table) {
                $count = DB::table($table)->count();

                if ($dryRun) {
                    $this->line(sprintf('  [dry-run] %-35s %d dòng', $table, $count));

                    continue;
                }

                DB::table($table)->delete();

                $this->info(sprintf('Đã xoá %-35s %d dòng', $table, $count));
            }
        } finally {
            DB::statement($driver === 'sqlite'
                ? 'PRAGMA foreign_keys = ON'
                : 'SET FOREIGN_KEY_CHECKS = 1');
        }

        if ($dryRun) {
            $this->warn('DRY-RUN: chưa có dữ liệu nào bị xoá.');
        } else {
            $this->info('Hoàn tất. Landing page, campaign, form template và cấu hình được giữ nguyên.');
        }

        return self::SUCCESS;
    }
}
