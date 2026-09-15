<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ResetDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->withoutForeignKeys(function (): void {
            $tables = [
                'email_events', 'email_tracked_links', 'email_messages', 'email_campaign_recipients',
                'email_campaigns', 'email_suppressions', 'email_templates', 'email_template_categories',
                'email_sending_accounts', 'email_sending_domains', 'marketing_audit_logs',
                'marketing_automation_runs', 'marketing_utm_events', 'marketing_email_campaign_links',
                'marketing_contact_list_members', 'marketing_segments', 'marketing_contact_lists',
                'marketing_landing_page_submissions', 'marketing_landing_page_views',
                'marketing_landing_pages', 'marketing_form_fields', 'marketing_form_templates',
                'marketing_campaigns', 'crm_audit_logs', 'crm_customer_interactions',
                'crm_customer_distribution_items', 'crm_customer_distribution_batches',
                'crm_customer_assignments', 'crm_customers', 'crm_lead_distribution_events',
                'crm_lead_activities', 'crm_qualification_notes', 'crm_contact_qualifications',
                'crm_leads', 'crm_company_assignments', 'crm_company_match_candidates',
                'crm_company_contacts', 'crm_business_contact_profiles', 'crm_personal_contact_profiles',
                'crm_companies', 'crm_contacts', 'crm_agent_profiles',
                'hr_employee_business_functions', 'hr_employee_availabilities', 'hr_employees',
                'hr_positions', 'hr_departments', 'system_translations',
            ];

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }

            User::query()->where('email', '!=', 'admin@dth.local')->delete();

            if (! User::query()->where('email', 'admin@dth.local')->exists()) {
                User::query()->create([
                    'name' => 'DTH Administrator',
                    'email' => 'admin@dth.local',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]);
            }
        });
    }

    private function withoutForeignKeys(callable $callback): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        try {
            $callback();
        } finally {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            } elseif ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON');
            }
        }
    }
}
