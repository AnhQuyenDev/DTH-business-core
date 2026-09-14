<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CrmDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $adminId = DB::table('users')->where('email', 'admin@dth.local')->value('id');

        $staffIds = collect([
            ['staff_code' => 'STF-DEMO-01', 'name' => 'Nguyen Minh Anh', 'email' => 'anh.nguyen@dth.local', 'phone' => '0901000001', 'department' => 'Sales', 'position' => 'Account Executive', 'employment_status' => 'active', 'lead_capacity' => 80, 'customer_capacity' => 120, 'distribution_weight' => 1.2, 'user_id' => $adminId],
            ['staff_code' => 'STF-DEMO-02', 'name' => 'Tran Bao Ngoc', 'email' => 'ngoc.tran@dth.local', 'phone' => '0901000002', 'department' => 'Customer Success', 'position' => 'Customer Success Specialist', 'employment_status' => 'active', 'lead_capacity' => 60, 'customer_capacity' => 100, 'distribution_weight' => 1.0, 'user_id' => null],
            ['staff_code' => 'STF-DEMO-03', 'name' => 'Le Quang Huy', 'email' => 'huy.le@dth.local', 'phone' => '0901000003', 'department' => 'Sales', 'position' => 'Business Consultant', 'employment_status' => 'active', 'lead_capacity' => 70, 'customer_capacity' => 100, 'distribution_weight' => 0.8, 'user_id' => null],
            ['staff_code' => 'STF-DEMO-04', 'name' => 'Pham Thu Ha', 'email' => 'ha.pham@dth.local', 'phone' => '0901000004', 'department' => 'Operations', 'position' => 'Operations Manager', 'employment_status' => 'inactive', 'lead_capacity' => 40, 'customer_capacity' => 60, 'distribution_weight' => 0.5, 'user_id' => null],
            ['staff_code' => 'STF-DEMO-05', 'name' => 'Vo Duc Minh', 'email' => 'minh.vo@dth.local', 'phone' => '0901000005', 'department' => 'Sales', 'position' => 'Sales Development Representative', 'employment_status' => 'active', 'lead_capacity' => 50, 'customer_capacity' => 80, 'distribution_weight' => 1.5, 'user_id' => null],
        ])->map(function (array $row) use ($now): int {
            return DB::table('crm_staff')->insertGetId($row + ['created_at' => $now, 'updated_at' => $now]);
        });

        foreach ($staffIds as $index => $staffId) {
            foreach (range(0, 6) as $day) {
                DB::table('crm_staff_availabilities')->insert(['staff_id' => $staffId, 'date' => $now->copy()->addDays($day)->toDateString(), 'status' => $index === 3 ? 'leave' : ($day === 5 ? 'remote' : 'working'), 'note' => $index === 3 ? 'Demo leave schedule' : null, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        $contactRows = [];
        foreach (range(1, 12) as $index) {
            $business = $index > 7;
            $contactRows[] = [
                'contact_code' => 'CNT-DEMO-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT), 'type' => $business ? 'business' : 'personal', 'display_name' => $business ? 'Business Contact '.$index : 'Personal Contact '.$index,
                'email' => $business ? "contact{$index}@company{$index}.example" : "person{$index}@example.com", 'phone' => '091200'.str_pad((string) $index, 4, '0', STR_PAD_LEFT), 'source' => ['landing_page', 'email_campaign', 'referral', 'direct'][$index % 4],
                'tags' => json_encode($index % 2 === 0 ? ['priority', 'demo'] : ['new']), 'metadata' => json_encode(['demo' => true, 'source_index' => $index]), 'created_at' => $now->copy()->subDays($index), 'updated_at' => $now,
            ];
        }
        $contactIds = collect($contactRows)->map(fn (array $row): int => DB::table('crm_contacts')->insertGetId($row));

        $companyIds = collect([
            ['company_code' => 'CMP-DEMO-01', 'legal_name' => 'VietStar Technology', 'tax_code' => '0101234501', 'email_domain' => 'vietstar.example', 'website' => 'https://vietstar.example', 'phone' => '0243000001', 'industry' => 'Technology', 'address' => '12 Cau Giay, Ha Noi', 'province' => 'Ha Noi', 'lifecycle_stage' => 'customer', 'account_owner_staff_id' => $staffIds[0]],
            ['company_code' => 'CMP-DEMO-02', 'legal_name' => 'GreenHub Services', 'tax_code' => '0101234502', 'email_domain' => 'greenhub.example', 'website' => 'https://greenhub.example', 'phone' => '0283000002', 'industry' => 'Services', 'address' => '88 Nguyen Hue, Ho Chi Minh', 'province' => 'Ho Chi Minh', 'lifecycle_stage' => 'qualified', 'account_owner_staff_id' => $staffIds[1]],
            ['company_code' => 'CMP-DEMO-03', 'legal_name' => 'Mekong Retail', 'tax_code' => '0101234503', 'email_domain' => 'mekong.example', 'website' => 'https://mekong.example', 'phone' => '0292000003', 'industry' => 'Retail', 'address' => '20 Tran Hung Dao, Can Tho', 'province' => 'Can Tho', 'lifecycle_stage' => 'prospect', 'account_owner_staff_id' => $staffIds[2]],
            ['company_code' => 'CMP-DEMO-04', 'legal_name' => 'Lotus Manufacturing', 'tax_code' => '0101234504', 'email_domain' => 'lotus.example', 'website' => null, 'phone' => '0236000004', 'industry' => 'Manufacturing', 'address' => '4 Le Loi, Da Nang', 'province' => 'Da Nang', 'lifecycle_stage' => 'inactive', 'account_owner_staff_id' => null],
            ['company_code' => 'CMP-DEMO-05', 'legal_name' => 'Nova Education', 'tax_code' => '0101234505', 'email_domain' => 'nova.example', 'website' => 'https://nova.example', 'phone' => '0243000005', 'industry' => 'Education', 'address' => '5 Hoang Dieu, Ha Noi', 'province' => 'Ha Noi', 'lifecycle_stage' => 'prospect', 'account_owner_staff_id' => $staffIds[4]],
        ])->map(function (array $row) use ($now): int {
            $row['normalized_name'] = strtolower($row['legal_name']);
            $row['normalized_phone'] = $row['phone'];
            return DB::table('crm_companies')->insertGetId($row + ['country_code' => 'VN', 'metadata' => json_encode(['demo' => true]), 'created_at' => $now, 'updated_at' => $now]);
        });

        foreach (range(0, 6) as $index) {
            DB::table('crm_personal_contact_profiles')->insert(['contact_id' => $contactIds[$index], 'first_name' => 'Personal', 'last_name' => 'Contact '.$index, 'date_of_birth' => $now->copy()->subYears(25 + $index)->toDateString(), 'gender' => ['male', 'female', 'other'][$index % 3], 'address_line' => 'Demo address '.$index, 'province' => ['Ha Noi', 'Da Nang', 'Ho Chi Minh'][$index % 3], 'country' => 'Vietnam', 'occupation' => ['Founder', 'Marketing Manager', 'Operations Lead'][$index % 3], 'service_interest' => ['crm', 'business-pro', 'email'][$index % 3], 'expected_budget' => 20000000 + ($index * 5000000), 'metadata' => json_encode(['demo' => true]), 'created_at' => $now, 'updated_at' => $now]);
        }
        foreach (range(7, 11) as $index) {
            $companyId = $companyIds[($index - 7) % $companyIds->count()];
            DB::table('crm_business_contact_profiles')->insert(['contact_id' => $contactIds[$index], 'company_id' => $companyId, 'company_name' => DB::table('crm_companies')->where('id', $companyId)->value('legal_name'), 'tax_code' => DB::table('crm_companies')->where('id', $companyId)->value('tax_code'), 'company_address' => 'Demo company address', 'legal_representative' => 'Business Contact '.$index, 'contact_position' => ['CEO', 'Director', 'Manager'][$index % 3], 'business_email' => "contact{$index}@company{$index}.example", 'business_phone' => '091200'.str_pad((string) $index, 4, '0', STR_PAD_LEFT), 'industry' => 'Business services', 'tax_verification_status' => $index % 3 === 0 ? 'verified' : 'pending', 'created_at' => $now, 'updated_at' => $now]);
        }
        foreach (range(0, 4) as $index) {
            DB::table('crm_company_contacts')->insert(['company_id' => $companyIds[$index], 'contact_id' => $contactIds[$index + 7], 'job_title' => ['CEO', 'Director', 'Manager'][$index % 3], 'department' => ['Executive', 'Sales', 'Operations'][$index % 3], 'decision_role' => ['decision_maker', 'influencer', 'technical_contact', 'billing_contact', 'end_user'][$index], 'is_primary' => $index < 3, 'is_active' => $index !== 3, 'created_at' => $now, 'updated_at' => $now]);
        }

        $leadRows = [];
        $leadStatuses = ['new', 'active', 'duplicate', 'spam', 'closed', 'converted_to_opportunity'];
        foreach (range(1, 12) as $index) {
            $contactIndex = ($index - 1) % $contactIds->count();
            $companyId = $contactIndex >= 7 ? $companyIds[($contactIndex - 7) % $companyIds->count()] : null;
            $leadRows[] = ['lead_code' => 'LEAD-DEMO-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT), 'submission_reference' => 'SUB-DEMO-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT), 'contact_id' => $contactIds[$contactIndex], 'company_id' => $companyId, 'assigned_staff_id' => $staffIds[($index - 1) % $staffIds->count()], 'source' => ['landing_page', 'email', 'referral', 'direct'][$index % 4], 'source_detail' => 'Demo source '.$index, 'title' => ['CRM implementation', 'Business Pro consultation', 'Email automation', 'Marketing campaign'][($index - 1) % 4], 'service_interest' => ['crm', 'business-pro', 'email', 'marketing-automation'][($index - 1) % 4], 'service_reference' => 'SERVICE-'.str_pad((string) (($index % 4) + 1), 2, '0', STR_PAD_LEFT), 'estimated_value' => 25000000 + ($index * 10000000), 'intake_status' => $leadStatuses[$index % count($leadStatuses)], 'assigned_at' => $now->copy()->subDays($index), 'assigned_by_user_id' => $adminId, 'attribution' => json_encode(['utm_source' => 'demo']), 'form_answers' => json_encode(['demo' => true]), 'metadata' => json_encode(['demo' => true]), 'created_at' => $now->copy()->subDays($index + 2), 'updated_at' => $now];
        }
        $leadIds = collect($leadRows)->map(fn (array $row): int => DB::table('crm_leads')->insertGetId($row));

        $qualificationStatuses = ['new', 'assigned', 'contacting', 'follow_up', 'qualified', 'unqualified', 'converted', 'duplicate', 'spam', 'archived'];
        foreach (range(0, 9) as $index) {
            $leadId = $leadIds[$index];
            $qualificationId = DB::table('crm_contact_qualifications')->insertGetId(['lead_id' => $leadId, 'contact_id' => $contactIds[$index % $contactIds->count()], 'assigned_staff_id' => $staffIds[$index % $staffIds->count()], 'status' => $qualificationStatuses[$index], 'priority' => ['low', 'normal', 'high', 'urgent'][$index % 4], 'score' => 20 + ($index * 8), 'qualification_result' => in_array($qualificationStatuses[$index], ['qualified', 'converted'], true) ? 'qualified' : null, 'service_interest' => ['crm', 'business-pro', 'email', 'marketing-automation'][$index % 4], 'estimated_value' => 30000000 + ($index * 7000000), 'budget_status' => ['confirmed', 'estimated', 'unknown'][$index % 3], 'budget_amount' => 20000000 + ($index * 5000000), 'purchase_timeline' => ['immediate', 'within_1_month', 'within_3_months', 'within_6_months', 'later'][$index % 5], 'decision_role' => ['decision_maker', 'influencer', 'technical_contact'][$index % 3], 'qualification_note' => 'Qualification note for demo scenario '.$index, 'first_contacted_at' => $now->copy()->subDays($index), 'last_contacted_at' => $now->copy()->subDays(max(0, $index - 1)), 'next_follow_up_at' => $now->copy()->addDays($index + 1), 'qualified_at' => in_array($qualificationStatuses[$index], ['qualified', 'converted'], true) ? $now->copy()->subDays(2) : null, 'unqualified_reason' => $qualificationStatuses[$index] === 'unqualified' ? 'Budget is not approved yet.' : null, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('crm_qualification_notes')->insert(['qualification_id' => $qualificationId, 'staff_id' => $staffIds[$index % $staffIds->count()], 'note' => 'Follow-up note for qualification scenario '.$index, 'created_at' => $now, 'updated_at' => $now]);
        }

        $activityTypes = ['call', 'email', 'meeting', 'message', 'note', 'other'];
        foreach ($leadIds as $index => $leadId) {
            foreach (range(1, 3) as $activity) {
                DB::table('crm_lead_activities')->insert(['lead_id' => $leadId, 'staff_id' => $staffIds[($index + $activity) % $staffIds->count()], 'type' => $activityTypes[($index + $activity) % count($activityTypes)], 'status' => $activity === 3 ? 'pending' : 'completed', 'subject' => 'Lead activity '.$activity, 'content' => 'Demo activity history for lead '.$leadId, 'outcome' => $activity === 1 ? 'follow_up' : null, 'activity_at' => $now->copy()->subDays($index + $activity), 'next_follow_up_at' => $activity === 3 ? $now->copy()->addDays(2) : null, 'metadata' => json_encode(['demo' => true]), 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        $customerIds = collect();
        foreach (range(1, 8) as $index) {
            $contactId = $contactIds[($index - 1) % $contactIds->count()];
            $companyId = $index % 2 === 0 ? $companyIds[($index / 2 - 1) % $companyIds->count()] : null;
            $customerIds->push(DB::table('crm_customers')->insertGetId(['customer_code' => 'CUS-DEMO-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT), 'contact_id' => $contactId, 'company_id' => $companyId, 'origin_lead_id' => $leadIds[$index - 1], 'customer_type' => $companyId ? 'business' : 'personal', 'display_name' => $companyId ? 'Business Customer '.$index : 'Personal Customer '.$index, 'email' => DB::table('crm_contacts')->where('id', $contactId)->value('email'), 'normalized_email' => DB::table('crm_contacts')->where('id', $contactId)->value('normalized_email'), 'phone' => DB::table('crm_contacts')->where('id', $contactId)->value('phone'), 'normalized_phone' => DB::table('crm_contacts')->where('id', $contactId)->value('normalized_phone'), 'acquisition_source' => ['landing_page', 'email', 'referral', 'direct'][$index % 4], 'consent_status' => $index % 3 === 0 ? 'pending' : 'approved', 'status' => ['potential', 'active', 'inactive', 'churned'][$index % 4], 'lifecycle_stage' => ['new_customer', 'repeat', 'loyal', 'at_risk'][$index % 4], 'conversion_reason' => $index % 2 === 0 ? 'purchased' : 'qualified_lead', 'converted_at' => $now->copy()->subDays($index), 'converted_by_staff_id' => $staffIds[$index % $staffIds->count()], 'first_purchase_at' => $index % 2 === 0 ? $now->copy()->subDays($index + 2) : null, 'latest_purchase_at' => $index % 2 === 0 ? $now->copy()->subDays(1) : null, 'total_revenue' => $index % 2 === 0 ? 25000000 * $index : 0, 'priority' => ['low', 'normal', 'high', 'urgent'][$index % 4], 'next_follow_up_at' => $now->copy()->addDays($index), 'metadata' => json_encode(['demo' => true]), 'created_at' => $now, 'updated_at' => $now]));
        }
        foreach ($customerIds as $index => $customerId) {
            DB::table('crm_customer_assignments')->insert(['customer_id' => $customerId, 'staff_id' => $staffIds[$index % $staffIds->count()], 'assignment_type' => $index % 3 === 0 ? 'support' : 'owner', 'status' => $index % 4 === 0 ? 'inactive' : 'active', 'reason' => 'demo_assignment', 'assigned_by_user_id' => $adminId, 'starts_at' => $now->copy()->subDays(10), 'ends_at' => $index % 4 === 0 ? $now->copy()->subDay() : null, 'created_at' => $now, 'updated_at' => $now]);
            foreach (range(1, 2) as $interaction) {
                DB::table('crm_customer_interactions')->insert(['customer_id' => $customerId, 'staff_id' => $staffIds[$index % $staffIds->count()], 'interaction_type' => ['call', 'email', 'meeting', 'message', 'note', 'support'][($index + $interaction) % 6], 'subject' => 'Customer care interaction '.$interaction, 'content' => 'Demo customer interaction history.', 'outcome' => $interaction === 1 ? 'follow_up' : 'completed', 'interaction_at' => $now->copy()->subDays($interaction), 'next_follow_up_at' => $interaction === 1 ? $now->copy()->addDays(3) : null, 'is_support_action' => $interaction === 2, 'status' => $interaction === 2 ? 'pending' : 'completed', 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        $batchId = DB::table('crm_customer_distribution_batches')->insertGetId(['batch_code' => 'DIST-DEMO-01', 'batch_type' => 'customer', 'strategy' => 'round_robin', 'status' => 'completed', 'total_items' => $customerIds->count(), 'processed_items' => $customerIds->count(), 'criteria' => json_encode(['status' => 'potential', 'region' => 'all']), 'created_by_user_id' => $adminId, 'created_at' => $now, 'updated_at' => $now]);
        foreach ($customerIds as $index => $customerId) {
            DB::table('crm_customer_distribution_items')->insert(['distribution_batch_id' => $batchId, 'customer_id' => $customerId, 'original_owner_staff_id' => null, 'assigned_staff_id' => $staffIds[$index % $staffIds->count()], 'assignment_type' => 'owner', 'result_status' => $index % 4 === 0 ? 'skipped' : 'assigned', 'reason' => $index % 4 === 0 ? 'Staff capacity reached' : 'Round robin demo assignment', 'created_at' => $now, 'updated_at' => $now]);
        }
        foreach ($leadIds->take(8) as $index => $leadId) {
            DB::table('crm_lead_distribution_events')->insert(['lead_id' => $leadId, 'from_staff_id' => null, 'to_staff_id' => $staffIds[$index % $staffIds->count()], 'strategy' => 'round_robin', 'event' => 'assigned', 'status' => $index % 3 === 0 ? 'pending' : 'completed', 'reason' => 'Demo lead distribution', 'actor_user_id' => $adminId, 'responded_at' => $index % 3 === 0 ? null : $now->copy()->subDay(), 'created_at' => $now, 'updated_at' => $now]);
        }
        foreach ($companyIds as $index => $companyId) {
            if ($index < 4) {
                DB::table('crm_company_assignments')->insert(['company_id' => $companyId, 'staff_id' => $staffIds[$index % $staffIds->count()], 'assignment_type' => 'owner', 'status' => $index === 3 ? 'inactive' : 'active', 'reason' => 'Demo ownership', 'assigned_by_user_id' => $adminId, 'starts_at' => $now->copy()->subDays(30), 'ends_at' => $index === 3 ? $now->copy()->subDay() : null, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        DB::table('crm_company_match_candidates')->insert([
            ['submission_reference' => 'SUB-DEMO-MATCH-01', 'contact_id' => $contactIds[7], 'suggested_company_id' => $companyIds[0], 'confidence' => 92, 'matched_by' => 'email_domain', 'status' => 'pending', 'reviewed_by_user_id' => null, 'reviewed_at' => null, 'created_at' => $now, 'updated_at' => $now],
            ['submission_reference' => 'SUB-DEMO-MATCH-02', 'contact_id' => $contactIds[8], 'suggested_company_id' => $companyIds[1], 'confidence' => 98, 'matched_by' => 'tax_code', 'status' => 'accepted', 'reviewed_by_user_id' => $adminId, 'reviewed_at' => $now->copy()->subDay(), 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('crm_audit_logs')->insert(['event' => 'demo.seed.completed', 'subject_type' => 'demo', 'subject_id' => 'crm', 'actor_user_id' => $adminId, 'changes' => json_encode(['records' => ['staff' => $staffIds->count(), 'contacts' => $contactIds->count(), 'companies' => $companyIds->count(), 'leads' => $leadIds->count(), 'customers' => $customerIds->count()]]), 'context' => json_encode(['source' => 'CrmDemoSeeder']), 'created_at' => $now, 'updated_at' => $now]);
    }
}
