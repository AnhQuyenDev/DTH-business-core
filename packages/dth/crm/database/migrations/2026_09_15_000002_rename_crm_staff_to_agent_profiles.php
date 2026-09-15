<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CRM no longer owns employee/staff master data. The HR module owns the
     * employee record, while CRM stores only the assignment profile.
     *
     * This migration intentionally renames the existing table/columns in place
     * so all primary keys and assignment references remain unchanged.
     */
    public function up(): void
    {
        if (Schema::hasTable('crm_staff') && Schema::hasTable('crm_agent_profiles')) {
            throw new \RuntimeException(
                'Both crm_staff and crm_agent_profiles exist. Resolve the duplicate CRM assignment-profile tables before migrating.'
            );
        }

        if (Schema::hasTable('crm_staff')) {
            Schema::rename('crm_staff', 'crm_agent_profiles');
        }

        if (! Schema::hasTable('crm_agent_profiles')) {
            return;
        }

        foreach ($this->columnRenames() as $table => $columns) {
            foreach ($columns as $from => $to) {
                $this->renameColumnIfNeeded($table, $from, $to);
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->columnRenames(), true) as $table => $columns) {
            foreach (array_reverse($columns, true) as $from => $to) {
                $this->renameColumnIfNeeded($table, $to, $from);
            }
        }

        if (Schema::hasTable('crm_agent_profiles') && ! Schema::hasTable('crm_staff')) {
            Schema::rename('crm_agent_profiles', 'crm_staff');
        }
    }

    /** @return array<string, array<string, string>> */
    private function columnRenames(): array
    {
        return [
            'crm_companies' => [
                'account_owner_staff_id' => 'account_owner_agent_profile_id',
            ],
            'crm_company_assignments' => [
                'staff_id' => 'agent_profile_id',
            ],
            'crm_leads' => [
                'assigned_staff_id' => 'assigned_agent_profile_id',
            ],
            'crm_contact_qualifications' => [
                'assigned_staff_id' => 'assigned_agent_profile_id',
                'qualified_by_staff_id' => 'qualified_by_agent_profile_id',
            ],
            'crm_qualification_notes' => [
                'staff_id' => 'agent_profile_id',
            ],
            'crm_lead_activities' => [
                'staff_id' => 'agent_profile_id',
            ],
            'crm_lead_distribution_events' => [
                'from_staff_id' => 'from_agent_profile_id',
                'to_staff_id' => 'to_agent_profile_id',
            ],
            'crm_customers' => [
                'converted_by_staff_id' => 'converted_by_agent_profile_id',
            ],
            'crm_customer_assignments' => [
                'staff_id' => 'agent_profile_id',
            ],
            'crm_customer_distribution_items' => [
                'original_owner_staff_id' => 'original_owner_agent_profile_id',
                'assigned_staff_id' => 'assigned_agent_profile_id',
            ],
            'crm_customer_interactions' => [
                'staff_id' => 'agent_profile_id',
            ],
        ];
    }

    private function renameColumnIfNeeded(string $table, string $from, string $to): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $hasFrom = Schema::hasColumn($table, $from);
        $hasTo = Schema::hasColumn($table, $to);

        if ($hasFrom && $hasTo) {
            throw new \RuntimeException("Both {$table}.{$from} and {$table}.{$to} exist. Resolve the duplicate assignment columns before migrating.");
        }

        if ($hasTo || ! $hasFrom) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($from, $to): void {
            $blueprint->renameColumn($from, $to);
        });
    }
};
