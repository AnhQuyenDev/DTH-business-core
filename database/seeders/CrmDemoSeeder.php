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
            ['staff_code' => 'STF-DEMO-01', 'name' => 'Nguyen Minh Anh', 'email' => 'anh.nguyen@dth.local', 'phone' => '0901000001', 'department' => 'Sales', 'position' => 'Account Executive', 'lead_capacity' => 80, 'customer_capacity' => 120, 'distribution_weight' => 1.2, 'user_id' => $adminId],
            ['staff_code' => 'STF-DEMO-02', 'name' => 'Tran Bao Ngoc', 'email' => 'ngoc.tran@dth.local', 'phone' => '0901000002', 'department' => 'Customer Success', 'position' => 'Customer Success Specialist', 'lead_capacity' => 60, 'customer_capacity' => 100, 'distribution_weight' => 1.0, 'user_id' => null],
            ['staff_code' => 'STF-DEMO-03', 'name' => 'Le Quang Huy', 'email' => 'huy.le@dth.local', 'phone' => '0901000003', 'department' => 'Sales', 'position' => 'Business Consultant', 'lead_capacity' => 70, 'customer_capacity' => 100, 'distribution_weight' => 0.8, 'user_id' => null],
        ])->map(function (array $staff) use ($now): int {
            return DB::table('crm_staff')->insertGetId($staff + ['employment_status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        });

        $contactIds = collect([
            ['contact_code' => 'CNT-DEMO-01', 'type' => 'personal', 'display_name' => 'Pham Gia Bao', 'email' => 'bao.pham@example.com', 'phone' => '0912000001', 'source' => 'landing_page', 'tags' => ['priority', 'demo']],
            ['contact_code' => 'CNT-DEMO-02', 'type' => 'personal', 'display_name' => 'Doanh Nguyen', 'email' => 'doanh.nguyen@example.com', 'phone' => '0912000002', 'source' => 'email_campaign', 'tags' => ['newsletter']],
            ['contact_code' => 'CNT-DEMO-03', 'type' => 'business', 'display_name' => 'Nguyen Thi Lan', 'email' => 'lan@vietstar.example', 'phone' => '0912000003', 'source' => 'landing_page', 'tags' => ['decision_maker']],
            ['contact_code' => 'CNT-DEMO-04', 'type' => 'business', 'display_name' => 'Bui Duc Long', 'email' => 'long@greenhub.example', 'phone' => '0912000004', 'source' => 'referral', 'tags' => ['influencer']],
            ['contact_code' => 'CNT-DEMO-05', 'type' => 'personal', 'display_name' => 'Hoang Mai', 'email' => 'mai.hoang@example.com', 'phone' => '0912000005', 'source' => 'direct', 'tags' => ['follow_up']],
            ['contact_code' => 'CNT-DEMO-06', 'type' => 'personal', 'display_name' => 'Vu Thanh Son', 'email' => 'son.vu@example.com', 'phone' => '0912000006', 'source' => 'landing_page', 'tags' => ['new']],
        ])->map(function (array $contact) use ($now): int {
            $contact['normalized_email'] = strtolower($contact['email']);
            $contact['normalized_phone'] = $contact['phone'];
            $contact['tags'] = json_encode($contact['tags']);
            return DB::table('crm_contacts')->insertGetId($contact + ['metadata' => json_encode(['demo' => true]), 'created_at' => $now, 'updated_at' => $now]);
        });

        $companyIds = collect([
            ['company_code' => 'CMP-DEMO-01', 'legal_name' => 'VietStar Technology', 'tax_code' => '0101234567', 'email_domain' => 'vietstar.example', 'website' => 'https://vietstar.example', 'phone' => '0243000001', 'industry' => 'Cong nghe', 'address' => '12 Cau Giay, Ha Noi', 'province' => 'Ha Noi', 'lifecycle_stage' => 'customer', 'account_owner_staff_id' => $staffIds[0]],
            ['company_code' => 'CMP-DEMO-02', 'legal_name' => 'GreenHub Services', 'tax_code' => '0101234568', 'email_domain' => 'greenhub.example', 'website' => 'https://greenhub.example', 'phone' => '0283000002', 'industry' => 'Dich vu', 'address' => '88 Nguyen Hue, TP HCM', 'province' => 'Ho Chi Minh', 'lifecycle_stage' => 'qualified', 'account_owner_staff_id' => $staffIds[1]],
            ['company_code' => 'CMP-DEMO-03', 'legal_name' => 'Mekong Retail', 'tax_code' => '0101234569', 'email_domain' => 'mekong.example', 'website' => 'https://mekong.example', 'phone' => '0292000003', 'industry' => 'Ban le', 'address' => '20 Tran Hung Dao, Can Tho', 'province' => 'Can Tho', 'lifecycle_stage' => 'prospect', 'account_owner_staff_id' => $staffIds[2]],
        ])->map(function (array $company) use ($now): int {
            $company['normalized_name'] = strtolower($company['legal_name']);
            $company['normalized_phone'] = $company['phone'];
            return DB::table('crm_companies')->insertGetId($company + ['country_code' => 'VN', 'metadata' => json_encode(['demo' => true]), 'created_at' => $now, 'updated_at' => $now]);
        });

        DB::table('crm_personal_contact_profiles')->insert([
            ['contact_id' => $contactIds[0], 'first_name' => 'Gia Bao', 'last_name' => 'Pham', 'gender' => 'male', 'province' => 'Ha Noi', 'occupation' => 'Founder', 'service_interest' => 'business-pro', 'expected_budget' => 50000000, 'created_at' => $now, 'updated_at' => $now],
            ['contact_id' => $contactIds[1], 'first_name' => 'Doanh', 'last_name' => 'Nguyen', 'gender' => 'female', 'province' => 'Da Nang', 'occupation' => 'Marketing Manager', 'service_interest' => 'marketing-automation', 'expected_budget' => 30000000, 'created_at' => $now, 'updated_at' => $now],
            ['contact_id' => $contactIds[4], 'first_name' => 'Mai', 'last_name' => 'Hoang', 'gender' => 'female', 'province' => 'Ho Chi Minh', 'occupation' => 'Operations Lead', 'service_interest' => 'crm', 'expected_budget' => 40000000, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('crm_business_contact_profiles')->insert([
            ['contact_id' => $contactIds[2], 'company_id' => $companyIds[0], 'company_name' => 'VietStar Technology', 'tax_code' => '0101234567', 'company_address' => '12 Cau Giay, Ha Noi', 'legal_representative' => 'Nguyen Thi Lan', 'contact_position' => 'CEO', 'business_email' => 'lan@vietstar.example', 'business_phone' => '0912000003', 'industry' => 'Cong nghe', 'tax_verification_status' => 'verified', 'created_at' => $now, 'updated_at' => $now],
            ['contact_id' => $contactIds[3], 'company_id' => $companyIds[1], 'company_name' => 'GreenHub Services', 'tax_code' => '0101234568', 'company_address' => '88 Nguyen Hue, TP HCM', 'legal_representative' => 'Bui Duc Long', 'contact_position' => 'Operations Director', 'business_email' => 'long@greenhub.example', 'business_phone' => '0912000004', 'industry' => 'Dich vu', 'tax_verification_status' => 'pending', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('crm_company_contacts')->insert([
            ['company_id' => $companyIds[0], 'contact_id' => $contactIds[2], 'job_title' => 'CEO', 'department' => 'Executive', 'decision_role' => 'decision_maker', 'is_primary' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['company_id' => $companyIds[1], 'contact_id' => $contactIds[3], 'job_title' => 'Operations Director', 'department' => 'Operations', 'decision_role' => 'influencer', 'is_primary' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $leadIds = collect([
            ['lead_code' => 'LEAD-DEMO-01', 'contact_id' => $contactIds[0], 'company_id' => null, 'assigned_staff_id' => $staffIds[0], 'source' => 'landing_page', 'title' => 'Tu van Business Pro', 'service_interest' => 'business-pro', 'estimated_value' => 50000000, 'intake_status' => 'qualified'],
            ['lead_code' => 'LEAD-DEMO-02', 'contact_id' => $contactIds[2], 'company_id' => $companyIds[0], 'assigned_staff_id' => $staffIds[0], 'source' => 'email', 'title' => 'Trien khai CRM doanh nghiep', 'service_interest' => 'crm', 'estimated_value' => 120000000, 'intake_status' => 'active'],
            ['lead_code' => 'LEAD-DEMO-03', 'contact_id' => $contactIds[3], 'company_id' => $companyIds[1], 'assigned_staff_id' => $staffIds[1], 'source' => 'referral', 'title' => 'Tu dong hoa Marketing', 'service_interest' => 'marketing-automation', 'estimated_value' => 85000000, 'intake_status' => 'new'],
            ['lead_code' => 'LEAD-DEMO-04', 'contact_id' => $contactIds[4], 'company_id' => null, 'assigned_staff_id' => $staffIds[2], 'source' => 'direct', 'title' => 'Ket noi Email va CRM', 'service_interest' => 'email', 'estimated_value' => 35000000, 'intake_status' => 'converted'],
        ])->map(function (array $lead) use ($now, $staffIds): int {
            return DB::table('crm_leads')->insertGetId($lead + ['assigned_at' => $now->copy()->subDays(2), 'assigned_by_user_id' => $staffIds[0] === null ? null : DB::table('crm_staff')->where('id', $staffIds[0])->value('user_id'), 'form_answers' => json_encode(['demo' => true]), 'metadata' => json_encode(['demo' => true]), 'created_at' => $now->copy()->subDays(4), 'updated_at' => $now]);
        });

        foreach ([$leadIds[0], $leadIds[1], $leadIds[2]] as $index => $leadId) {
            DB::table('crm_contact_qualifications')->insert([
                'lead_id' => $leadId,
                'contact_id' => $contactIds[$index === 2 ? 3 : $index],
                'assigned_staff_id' => $staffIds[$index % 3],
                'status' => $index === 0 ? 'qualified' : 'qualifying',
                'priority' => $index === 0 ? 'high' : 'normal',
                'score' => [86, 72, 58][$index],
                'qualification_result' => $index === 0 ? 'qualified' : null,
                'service_interest' => ['business-pro', 'crm', 'marketing-automation'][$index],
                'estimated_value' => [50000000, 120000000, 85000000][$index],
                'budget_status' => $index === 0 ? 'approved' : 'reviewing',
                'budget_amount' => [50000000, 120000000, 85000000][$index],
                'purchase_timeline' => ['this_month', 'next_quarter', 'this_quarter'][$index],
                'decision_role' => $index === 0 ? 'decision_maker' : 'influencer',
                'qualification_note' => 'Du lieu demo de hien thi quy trinh qualification.',
                'next_follow_up_at' => $now->copy()->addDays($index + 1),
                'qualified_at' => $index === 0 ? $now->copy()->subDay() : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $qualificationId = DB::getPdo()->lastInsertId();
            DB::table('crm_qualification_notes')->insert(['qualification_id' => $qualificationId, 'staff_id' => $staffIds[$index % 3], 'note' => 'Da xac nhan nhu cau va ngan sach voi khach hang.', 'created_at' => $now, 'updated_at' => $now]);
        }

        foreach ($leadIds as $index => $leadId) {
            DB::table('crm_lead_activities')->insert([
                'lead_id' => $leadId,
                'staff_id' => $staffIds[$index % 3],
                'type' => ['call', 'meeting', 'email', 'note'][$index],
                'status' => 'completed',
                'subject' => 'Hoat dong cham soc Lead demo',
                'content' => 'Da lien he va ghi nhan nhu cau cua khach hang.',
                'outcome' => $index === 0 ? 'qualified' : 'follow_up',
                'activity_at' => $now->copy()->subDays($index + 1),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $customerIds = collect([
            ['customer_code' => 'CUS-DEMO-01', 'contact_id' => $contactIds[0], 'company_id' => null, 'origin_lead_id' => $leadIds[0], 'customer_type' => 'personal', 'display_name' => 'Pham Gia Bao', 'email' => 'bao.pham@example.com', 'phone' => '0912000001', 'status' => 'active', 'lifecycle_stage' => 'new_customer', 'acquisition_source' => 'landing_page', 'total_revenue' => 50000000, 'priority' => 'high'],
            ['customer_code' => 'CUS-DEMO-02', 'contact_id' => $contactIds[2], 'company_id' => $companyIds[0], 'origin_lead_id' => $leadIds[1], 'customer_type' => 'business', 'display_name' => 'VietStar Technology', 'email' => 'lan@vietstar.example', 'phone' => '0912000003', 'status' => 'active', 'lifecycle_stage' => 'loyal', 'acquisition_source' => 'email', 'total_revenue' => 180000000, 'priority' => 'high'],
            ['customer_code' => 'CUS-DEMO-03', 'contact_id' => $contactIds[4], 'company_id' => null, 'origin_lead_id' => $leadIds[3], 'customer_type' => 'personal', 'display_name' => 'Hoang Mai', 'email' => 'mai.hoang@example.com', 'phone' => '0912000005', 'status' => 'potential', 'lifecycle_stage' => 'new_customer', 'acquisition_source' => 'direct', 'total_revenue' => 0, 'priority' => 'normal'],
        ])->map(function (array $customer) use ($now): int {
            $customer['normalized_email'] = strtolower($customer['email']);
            $customer['normalized_phone'] = $customer['phone'];
            return DB::table('crm_customers')->insertGetId($customer + ['consent_status' => 'approved', 'converted_at' => $now->copy()->subDays(5), 'metadata' => json_encode(['demo' => true]), 'created_at' => $now, 'updated_at' => $now]);
        });
        foreach ($customerIds as $index => $customerId) {
            DB::table('crm_customer_assignments')->insert(['customer_id' => $customerId, 'staff_id' => $staffIds[$index % 3], 'assignment_type' => 'owner', 'status' => 'active', 'reason' => 'demo_seed', 'assigned_by_user_id' => $adminId, 'starts_at' => $now->copy()->subDays(5), 'created_at' => $now, 'updated_at' => $now]);
            DB::table('crm_customer_interactions')->insert(['customer_id' => $customerId, 'staff_id' => $staffIds[$index % 3], 'interaction_type' => 'call', 'subject' => 'Goi lai khach hang demo', 'content' => 'Trao doi ve tinh hinh su dung dich vu.', 'outcome' => 'follow_up', 'interaction_at' => $now->copy()->subDays($index + 1), 'status' => 'completed', 'created_at' => $now, 'updated_at' => $now]);
        }

        $batchId = DB::table('crm_customer_distribution_batches')->insertGetId(['batch_code' => 'DIST-DEMO-01', 'batch_type' => 'new_customer', 'strategy' => 'round_robin', 'status' => 'completed', 'total_items' => $customerIds->count(), 'processed_items' => $customerIds->count(), 'criteria' => json_encode(['status' => 'potential', 'region' => 'all']), 'created_by_user_id' => $adminId, 'created_at' => $now, 'updated_at' => $now]);
        foreach ($customerIds as $index => $customerId) {
            DB::table('crm_customer_distribution_items')->insert(['distribution_batch_id' => $batchId, 'customer_id' => $customerId, 'original_owner_staff_id' => null, 'assigned_staff_id' => $staffIds[$index % 3], 'assignment_type' => 'owner', 'result_status' => 'assigned', 'reason' => 'Demo distribution', 'created_at' => $now, 'updated_at' => $now]);
        }
    }
}
