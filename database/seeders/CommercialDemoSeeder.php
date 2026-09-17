<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommercialDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $adminId = DB::table('users')->where('email', 'admin@dth.local')->value('id');

        $employees = DB::table('hr_employees')
            ->whereIn('employee_code', ['EMP-DEMO-01', 'EMP-DEMO-02', 'EMP-DEMO-03', 'EMP-DEMO-04', 'EMP-DEMO-05'])
            ->get(['employee_code', 'full_name'])
            ->keyBy('employee_code');
        $employeeCodes = $employees->keys()->values();

        $contacts = DB::table('crm_contacts')->orderBy('id')->get(['contact_code', 'display_name']);
        $companies = DB::table('crm_companies')->orderBy('id')->get(['company_code', 'legal_name']);
        $leads = DB::table('crm_leads')->orderBy('id')->get(['lead_code', 'contact_id', 'company_id']);
        $contactsById = DB::table('crm_contacts')->pluck('contact_code', 'id');
        $companiesById = DB::table('crm_companies')->pluck('company_code', 'id');

        // --- Services & packages ---------------------------------------------------
        $serviceDefinitions = [
            ['service_code' => 'SVC-DEMO-CRM', 'name' => 'CRM Implementation', 'description' => 'End-to-end CRM rollout: leads, contacts, companies and customer care.', 'default_scope' => 'Discovery, configuration, data migration, training.', 'default_terms' => 'Net 30, 12-month support included.'],
            ['service_code' => 'SVC-DEMO-EMAIL', 'name' => 'Email Marketing Platform', 'description' => 'Transactional and campaign email sending with deliverability monitoring.', 'default_scope' => 'Domain setup, template design, campaign automation.', 'default_terms' => 'Net 15, monthly billing.'],
            ['service_code' => 'SVC-DEMO-MKT', 'name' => 'Marketing Automation', 'description' => 'Landing pages, audience segmentation and lifecycle campaigns.', 'default_scope' => 'Landing page build, segment setup, automation workflows.', 'default_terms' => 'Net 30, quarterly review.'],
            ['service_code' => 'SVC-DEMO-HR', 'name' => 'Human Resource Suite', 'description' => 'Employee, department and availability management.', 'default_scope' => 'Org chart import, roles setup, availability calendars.', 'default_terms' => 'Net 30, annual contract.'],
            ['service_code' => 'SVC-DEMO-CONSULT', 'name' => 'Business Consulting', 'description' => 'Advisory engagement for process and systems improvement.', 'default_scope' => 'Assessment workshops, roadmap, quarterly check-ins.', 'default_terms' => 'Net 15, milestone billing.'],
        ];

        $serviceIds = collect($serviceDefinitions)->map(function (array $row, int $index) use ($now, $adminId): int {
            return DB::table('commercial_services')->insertGetId($row + [
                'status' => $index === 4 ? 'inactive' : 'active',
                'sort_order' => $index,
                'metadata' => json_encode(['demo' => true]),
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $now->copy()->subDays(30 - $index),
                'updated_at' => $now,
            ]);
        });

        $packageDefinitions = [
            ['name' => 'Starter', 'audience_type' => 'both', 'billing_period' => 1, 'billing_period_unit' => 'month', 'unit' => 'package', 'default_quantity' => 1],
            ['name' => 'Professional', 'audience_type' => 'business', 'billing_period' => 12, 'billing_period_unit' => 'month', 'unit' => 'package', 'default_quantity' => 1],
            ['name' => 'Enterprise', 'audience_type' => 'business', 'billing_period' => 1, 'billing_period_unit' => 'year', 'unit' => 'package', 'default_quantity' => 1],
        ];

        $packageIds = collect();
        foreach ($serviceIds as $serviceIndex => $serviceId) {
            $serviceCode = $serviceDefinitions[$serviceIndex]['service_code'];
            foreach ($packageDefinitions as $tierIndex => $tier) {
                $packageIds->push(DB::table('commercial_service_packages')->insertGetId($tier + [
                    'service_id' => $serviceId,
                    'package_code' => $serviceCode.'-'.strtoupper(substr($tier['name'], 0, 3)),
                    'description' => $tier['name'].' tier for '.$serviceDefinitions[$serviceIndex]['name'],
                    'status' => 'active',
                    'sort_order' => $tierIndex,
                    'metadata' => json_encode(['demo' => true]),
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                    'created_at' => $now->copy()->subDays(29 - $serviceIndex),
                    'updated_at' => $now,
                ]));
            }
        }

        // --- Opportunities -----------------------------------------------------------
        $stages = ['discovery', 'qualified', 'proposal', 'negotiation', 'won', 'lost', 'cancelled'];
        $probabilities = ['discovery' => 20, 'qualified' => 50, 'proposal' => 70, 'negotiation' => 85, 'won' => 100, 'lost' => 0, 'cancelled' => 0];
        $titles = [
            'CRM rollout for sales team', 'Email deliverability upgrade', 'Landing page revamp',
            'Marketing automation onboarding', 'HR system migration', 'Business consulting retainer',
            'Customer care expansion', 'Lead distribution optimisation', 'Campaign analytics package',
            'Loyalty program integration', 'Multi-brand email setup', 'Sales pipeline audit',
            'Segmentation and personalization', 'Onboarding automation', 'Renewal upsell package',
            'Regional expansion support', 'API integration engagement', 'Data migration project',
            'Support desk augmentation', 'Annual strategy workshop',
        ];

        $opportunityCount = count($titles);
        $opportunityIds = collect();
        foreach ($titles as $index => $title) {
            $stage = $stages[$index % count($stages)];
            $serviceIndex = $index % $serviceIds->count();
            $serviceId = $serviceIds[$serviceIndex];
            $serviceCode = $serviceDefinitions[$serviceIndex]['service_code'];

            $lead = $leads->isNotEmpty() ? $leads[$index % $leads->count()] : null;
            $employeeCode = $employeeCodes->isNotEmpty() ? $employeeCodes[$index % $employeeCodes->count()] : null;
            $employeeName = $employeeCode ? $employees->get($employeeCode)->full_name : null;
            $contactCode = $lead && $lead->contact_id ? ($contactsById[$lead->contact_id] ?? null) : ($contacts->isNotEmpty() ? $contacts[$index % $contacts->count()]->contact_code : null);
            $contactName = $contactCode ? (DB::table('crm_contacts')->where('contact_code', $contactCode)->value('display_name')) : null;
            $companyCode = $lead && $lead->company_id ? ($companiesById[$lead->company_id] ?? null) : ($companies->isNotEmpty() ? $companies[$index % $companies->count()]->company_code : null);
            $companyName = $companyCode ? (DB::table('crm_companies')->where('company_code', $companyCode)->value('legal_name')) : null;

            $createdAt = $now->copy()->subDays($opportunityCount - $index);
            $isWon = $stage === 'won';
            $isLost = in_array($stage, ['lost', 'cancelled'], true);

            $opportunityIds->push(DB::table('commercial_opportunities')->insertGetId([
                'opportunity_code' => 'OPP-DEMO-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'lead_reference' => $lead?->lead_code,
                'lead_code_snapshot' => $lead?->lead_code,
                'contact_reference' => $contactCode,
                'contact_name_snapshot' => $contactName,
                'company_reference' => $companyCode,
                'company_name_snapshot' => $companyName,
                'assigned_employee_reference' => $employeeCode,
                'assigned_employee_name_snapshot' => $employeeName,
                'service_id' => $serviceId,
                'service_reference' => $serviceCode,
                'service_name_snapshot' => $serviceDefinitions[$serviceIndex]['name'],
                'title' => $title,
                'stage' => $stage,
                'estimated_value' => 15000000 + ($index * 3500000),
                'probability' => $probabilities[$stage],
                'expected_close_date' => $now->copy()->addDays(($index % 6) * 7 + 7)->toDateString(),
                'won_at' => $isWon ? $now->copy()->subDays(max(0, $index - 5)) : null,
                'lost_at' => $isLost ? $now->copy()->subDays(max(0, $index - 3)) : null,
                'lost_reason' => $stage === 'lost' ? 'Chose a competitor solution.' : ($stage === 'cancelled' ? 'Budget frozen for this quarter.' : null),
                'metadata' => json_encode(['demo' => true]),
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => $createdAt,
                'updated_at' => $now,
            ]));
        }

        // --- Opportunity interactions --------------------------------------------------
        $interactionTypes = ['call', 'email', 'meeting', 'note', 'proposal_sent'];
        $outcomes = ['follow_up', 'positive', 'no_answer', 'escalated', null];
        foreach ($opportunityIds as $index => $opportunityId) {
            $employeeCode = $employeeCodes->isNotEmpty() ? $employeeCodes[$index % $employeeCodes->count()] : null;
            foreach (range(1, 3) as $step) {
                $interactionAt = $now->copy()->subDays($opportunityCount - $index + $step);
                DB::table('commercial_opportunity_interactions')->insert([
                    'opportunity_id' => $opportunityId,
                    'employee_reference' => $employeeCode,
                    'interaction_type' => $interactionTypes[($index + $step) % count($interactionTypes)],
                    'subject' => 'Touchpoint '.$step.' for opportunity '.($index + 1),
                    'content' => 'Demo interaction log entry describing the conversation and next steps.',
                    'outcome' => $outcomes[($index + $step) % count($outcomes)],
                    'interaction_at' => $interactionAt,
                    'next_follow_up_at' => $step === 3 ? $now->copy()->addDays(3) : null,
                    'metadata' => json_encode(['demo' => true]),
                    'created_at' => $interactionAt,
                    'updated_at' => $interactionAt,
                ]);
            }
        }
    }
}
