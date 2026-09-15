<?php

namespace Dth\Crm\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CrmHealthCommand extends Command
{
    protected $signature = 'crm:health';
    protected $description = 'Validate DTH CRM runtime readiness, including Human Resource integration.';

    public function handle(): int
    {
        $tables = [
            'hr_employees',
            'crm_agent_profiles',
            'crm_contacts',
            'crm_companies',
            'crm_leads',
            'crm_contact_qualifications',
            'crm_customers',
        ];

        $ok = true;
        foreach ($tables as $table) {
            $available = Schema::hasTable($table);
            $this->line(($available ? 'PASS ' : 'FAIL ').$table);
            $ok = $ok && $available;
        }

        if (Schema::hasTable('crm_agent_profiles')) {
            foreach (['employee_id', 'assignment_enabled', 'lead_capacity', 'distribution_weight'] as $column) {
                $available = Schema::hasColumn('crm_agent_profiles', $column);
                $this->line(($available ? 'PASS ' : 'FAIL ').'crm_agent_profiles.'.$column);
                $ok = $ok && $available;
            }
        }

        $foreignKeys = [
            'crm_companies' => ['account_owner_agent_profile_id'],
            'crm_company_assignments' => ['agent_profile_id'],
            'crm_leads' => ['assigned_agent_profile_id'],
            'crm_contact_qualifications' => ['assigned_agent_profile_id', 'qualified_by_agent_profile_id'],
            'crm_qualification_notes' => ['agent_profile_id'],
            'crm_lead_activities' => ['agent_profile_id'],
            'crm_lead_distribution_events' => ['from_agent_profile_id', 'to_agent_profile_id'],
            'crm_customers' => ['converted_by_agent_profile_id'],
            'crm_customer_assignments' => ['agent_profile_id'],
            'crm_customer_distribution_items' => ['original_owner_agent_profile_id', 'assigned_agent_profile_id'],
            'crm_customer_interactions' => ['agent_profile_id'],
        ];

        foreach ($foreignKeys as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                $available = Schema::hasColumn($table, $column);
                $this->line(($available ? 'PASS ' : 'FAIL ').$table.'.'.$column);
                $ok = $ok && $available;
            }
        }

        $legacyTableGone = ! Schema::hasTable('crm_staff');
        $this->line(($legacyTableGone ? 'PASS ' : 'FAIL ').'legacy crm_staff removed');
        $ok = $ok && $legacyTableGone;

        $hrClass = class_exists(\Dth\HumanResource\Models\Employee::class);
        $this->line(($hrClass ? 'PASS ' : 'FAIL ').'Human Resource module');
        $ok = $ok && $hrClass;

        $this->line('Marketing LeadProvider: '.(
            interface_exists(\Dth\Marketing\Contracts\LeadProvider::class)
                ? 'available'
                : 'not installed'
        ));

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
