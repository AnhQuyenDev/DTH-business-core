<?php

namespace App\Console\Commands;

use Database\Seeders\V1AcceptanceTestSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ResetV1BusinessData extends Command
{
    protected $signature = 'v1:reset-business-data
        {--force : Required destructive confirmation}
        {--seed-test-accounts : Seed V1 organization and acceptance-test accounts after reset}';

    protected $description = 'Reset transactional + organization data while preserving Landing Pages, Campaigns, templates, catalog and company configuration.';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Lệnh reset dữ liệu V1 bị vô hiệu hóa trên môi trường production.');
            return self::FAILURE;
        }

        if (! $this->option('force')) {
            $this->error('Lệnh này xoá dữ liệu nghiệp vụ. Hãy chạy lại với --force.');
            return self::FAILURE;
        }

        $preserved = [
            'landing_pages', 'landing_page_forms', 'landing_page_templates',
            'landing_page_utm_urls', 'form_templates', 'form_fields',
            'marketing_campaigns', 'marketing_campaign_service', 'campaigns',
            'email_templates', 'email_template_categories', 'sending_accounts',
            'sending_domains', 'services', 'service_products', 'service_packages', 'service_package_products', 'price_books',
            'price_book_items', 'bank_accounts', 'company_settings',
            'ui_badge_styles', 'vn_banks', 'tags', 'segments', 'contact_lists',
            'custom_fields',
        ];

        $tables = [
            // Finance / quotation / sales transactions.
            'payment_revenue_lines', 'payment_attributions', 'payment_receipts', 'payments',
            'quotation_payment_notice_files', 'quotation_payment_notices',
            'quotation_approvals', 'quotation_confirmations', 'quotation_documents',
            'quotation_email_logs', 'quotation_items', 'quotations',
            'opportunity_contacts', 'opportunity_interactions', 'sales_opportunities',

            // Post-sale customer data, including V1 support tickets.
            'support_ticket_messages', 'support_tickets',
            'customer_interactions', 'customer_assignments',
            'customer_distribution_items', 'customer_distribution_batches',
            'customer_list_members', 'customer_lists', 'customer_tag', 'customers',

            // Lead / CRM transaction data.
            'lead_activities', 'contact_qualification_notes', 'contact_qualifications', 'leads',
            'company_assignments', 'company_contacts', 'company_match_candidates', 'companies',
            'business_contact_profiles', 'personal_contact_profiles',
            'contact_custom_field_values', 'contact_list_members', 'contact_tag',
            'campaign_recipients', 'email_events', 'tracked_links', 'contacts',
            'landing_page_submissions', 'landing_page_views', 'import_batches',
            'suppression_entries',

            // Organization and test identities are rebuilt by the V1 seeder.
            'price_book_access_rules', 'staff_availabilities', 'staff_business_functions',
            'staff', 'positions', 'departments', 'audit_logs', 'sessions', 'password_reset_tokens',
            'jobs', 'failed_jobs', 'job_batches', 'cache', 'cache_locks', 'notifications',
            'model_has_permissions', 'model_has_roles', 'users',

            // Sequence tables are reset so a fresh Golden Path is easy to read.
            'company_code_sequences', 'lead_code_sequences', 'opportunity_code_sequences',
        ];

        $this->warn('Đang xoá dữ liệu nghiệp vụ V1. Marketing assets và catalog sẽ được giữ lại.');

        // Remove private/generated files that belong exclusively to the
        // transactional rows being deleted. Company logo, Landing Page assets,
        // Email Templates and Marketing assets are intentionally preserved.
        $this->clearTransactionalFiles();

        // Preserved assets can reference identities/organization rows that are
        // intentionally reset. Null those optional references before identity
        // deletion so preserved Campaign/Landing/Catalog records never point
        // to removed users, staff or departments.
        $nullableIdentityColumns = [
            'created_by', 'updated_by', 'approved_by', 'owner_user_id',
            'created_by_user_id', 'updated_by_user_id', 'approved_by_user_id',
            'assigned_staff_id', 'verified_by_staff_id', 'department_id',
        ];

        foreach ($preserved as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $updates = [];
            foreach ($nullableIdentityColumns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    $updates[$column] = null;
                }
            }

            if ($updates !== []) {
                DB::table($table)->update($updates);
            }
        }

        // Email Campaign definitions are preserved, but their run state and
        // recipients/events are test transactions. Reset them to Draft so the
        // same campaign definition can be used again during fresh V1 UAT.
        if (Schema::hasTable('campaigns')) {
            DB::table('campaigns')->update([
                'status' => 'draft',
                'scheduled_at' => null,
                'sent_at' => null,
            ]);
        }

        Schema::disableForeignKeyConstraints();
        try {
            foreach ($tables as $table) {
                if (! Schema::hasTable($table) || in_array($table, $preserved, true)) {
                    continue;
                }

                DB::table($table)->delete();
                $this->line(' - cleared '.$table);
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        if ($this->option('seed-test-accounts')) {
            $this->call('db:seed', [
                '--class' => V1AcceptanceTestSeeder::class,
                '--force' => true,
            ]);
        }

        $this->newLine();
        $this->info('V1 reset hoàn tất. Landing Pages, Campaigns, templates, catalog và cấu hình công ty được giữ lại.');

        return self::SUCCESS;
    }

    private function clearTransactionalFiles(): void
    {
        $targets = [
            [(string) config('finance.evidence_disk', 'local'), 'payments/notices'],
            [(string) config('finance.receipt_disk', 'local'), 'payments/receipts'],
            ['local', 'quotations'],
        ];

        foreach ($targets as [$disk, $directory]) {
            try {
                Storage::disk($disk)->deleteDirectory($directory);
                $this->line(" - cleared files {$disk}:{$directory}");
            } catch (\Throwable $exception) {
                report($exception);
                $this->warn(" - không thể xoá file {$disk}:{$directory}; tiếp tục reset DB.");
            }
        }
    }
}
