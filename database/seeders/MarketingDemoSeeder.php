<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarketingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $adminId = DB::table('users')->where('email', 'admin@dth.local')->value('id');

        $campaigns = [
            ['name' => 'Thu hut khach hang Q3', 'slug' => 'thu-hut-khach-hang-q3', 'description' => 'Chien dich thu hut doanh nghiep quan tam giai phap DTH.', 'status' => 'active', 'start_date' => $now->copy()->subDays(14)->toDateString(), 'end_date' => $now->copy()->addDays(30)->toDateString(), 'budget' => 45000000, 'currency' => 'VND'],
            ['name' => 'Ra mat goi Business Pro', 'slug' => 'ra-mat-goi-business-pro', 'description' => 'Gioi thieu goi dich vu Business Pro den tap khach hang hien huu.', 'status' => 'draft', 'start_date' => $now->copy()->addDays(7)->toDateString(), 'end_date' => $now->copy()->addDays(60)->toDateString(), 'budget' => 28000000, 'currency' => 'VND'],
        ];
        foreach ($campaigns as $campaign) {
            DB::table('marketing_campaigns')->insert($campaign + ['created_by' => $adminId, 'context' => json_encode(['channel' => 'web', 'owner' => 'marketing']), 'created_at' => $now, 'updated_at' => $now]);
        }
        $campaignIds = DB::table('marketing_campaigns')->orderBy('id')->pluck('id');

        $forms = [
            ['name' => 'Form tu van ca nhan', 'slug' => 'form-tu-van-ca-nhan', 'description' => 'Thu thap nhu cau tu van cua khach hang ca nhan.', 'audience_type' => 'personal', 'status' => 'active', 'submit_button_text' => 'Nhan tu van', 'success_message' => 'Cam on ban. DTH se lien he som.'],
            ['name' => 'Form dang ky doanh nghiep', 'slug' => 'form-dang-ky-doanh-nghiep', 'description' => 'Thu thap thong tin doanh nghiep co nhu cau hop tac.', 'audience_type' => 'business', 'status' => 'active', 'submit_button_text' => 'Dang ky ngay', 'success_message' => 'Thong tin doanh nghiep da duoc tiep nhan.'],
        ];
        foreach ($forms as $form) {
            DB::table('marketing_form_templates')->insert($form + [
                'version' => 1,
                'schema' => json_encode(['version' => 1, 'layout' => 'stacked']),
                'html_body' => '<form><input name="email"><button type="submit">Gui</button></form>',
                'created_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        $formIds = DB::table('marketing_form_templates')->orderBy('id')->pluck('id');

        $fieldSets = [
            [$formIds[0], ['Ho va ten', 'Email', 'So dien thoai', 'Noi dung tu van']],
            [$formIds[1], ['Ten doanh nghiep', 'Nguoi lien he', 'Email cong ty', 'So dien thoai', 'Nhu cau dich vu']],
        ];
        foreach ($fieldSets as [$formId, $labels]) {
            foreach ($labels as $sort => $label) {
                $key = Str::slug($label, '_');
                $type = str_contains(strtolower($label), 'email') ? 'email' : (str_contains(strtolower($label), 'dien thoai') ? 'phone' : (str_contains(strtolower($label), 'noi dung') || str_contains(strtolower($label), 'nhu cau') ? 'textarea' : 'text'));
                DB::table('marketing_form_fields')->insert([
                    'form_template_id' => $formId,
                    'label' => $label,
                    'field_key' => $key,
                    'field_type' => $type,
                    'placeholder' => $label,
                    'is_required' => $sort < 3,
                    'contact_mapping' => match ($key) { 'email' => 'contact.email', 'so_dien_thoai' => 'contact.phone', 'ho_va_ten', 'nguoi_lien_he' => 'contact.display_name', default => null },
                    'sort_order' => $sort,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach ([
            ['name' => 'Landing tu van ca nhan', 'slug' => 'landing-tu-van-ca-nhan', 'marketing_campaign_id' => $campaignIds[0], 'personal_form_template_id' => $formIds[0], 'headline' => 'Tim giai phap phu hop cho ban', 'subheadline' => 'Doi ngu DTH san sang lang nghe nhu cau cua ban.', 'cta_text' => 'Nhan tu van', 'status' => 'published'],
            ['name' => 'Landing Business Pro', 'slug' => 'landing-business-pro', 'marketing_campaign_id' => $campaignIds[1], 'business_form_template_id' => $formIds[1], 'headline' => 'Tang toc van hanh doanh nghiep', 'subheadline' => 'Ket noi CRM, Marketing va Email trong mot quy trinh.', 'cta_text' => 'Dang ky demo', 'status' => 'draft'],
        ] as $page) {
            DB::table('marketing_landing_pages')->insert($page + [
                'page_title' => $page['name'].' | DTH Business',
                'content' => '<p>Giai phap van hanh doanh nghiep hien dai tu DTH.</p>',
                'tracking_source' => 'demo-seeder',
                'published_at' => $page['status'] === 'published' ? $now->copy()->subDays(3) : null,
                'created_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        $landingIds = DB::table('marketing_landing_pages')->orderBy('id')->pluck('id');

        foreach (range(1, 4) as $index) {
            $email = "lead.demo{$index}@example.com";
            DB::table('marketing_landing_page_submissions')->insert([
                'landing_page_id' => $landingIds[0],
                'marketing_campaign_id' => $campaignIds[0],
                'form_template_id' => $formIds[0],
                'submission_token' => (string) Str::uuid(),
                'payload_fingerprint' => hash('sha256', $email),
                'member_key' => hash('sha256', $email),
                'submission_type' => 'personal',
                'data' => json_encode(['ho_va_ten' => 'Lead Demo '.$index, 'email' => $email, 'so_dien_thoai' => '090000000'.$index]),
                'normalized_email' => $email,
                'normalized_phone' => '090000000'.$index,
                'display_name' => 'Lead Demo '.$index,
                'status' => $index === 4 ? 'processed' : 'received',
                'contact_action' => 'create_lead',
                'source' => 'landing_page',
                'utm_source' => 'facebook',
                'utm_medium' => 'paid_social',
                'utm_campaign' => 'thu-hut-khach-hang-q3',
                'submitted_at' => $now->copy()->subDays($index),
                'processed_at' => $index === 4 ? $now->copy()->subDays($index - 1) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        $submissionIds = DB::table('marketing_landing_page_submissions')->orderBy('id')->pluck('id');

        foreach ([
            ['name' => 'Danh sach khach hang tiem nang', 'slug' => 'khach-hang-tiem-nang', 'type' => 'newsletter'],
            ['name' => 'Khach hang quan tam Business', 'slug' => 'quan-tam-business', 'type' => 'segment'],
        ] as $list) {
            DB::table('marketing_contact_lists')->insert($list + ['description' => 'Du lieu demo de xem giao dien danh sach.', 'status' => 'active', 'created_by' => $adminId, 'created_at' => $now, 'updated_at' => $now]);
        }
        $listIds = DB::table('marketing_contact_lists')->orderBy('id')->pluck('id');
        foreach ($listIds as $listId) {
            foreach (range(1, 4) as $index) {
                $email = "member.{$listId}.{$index}@example.com";
                DB::table('marketing_contact_list_members')->insert([
                    'contact_list_id' => $listId,
                    'source_submission_id' => $submissionIds[($index - 1) % $submissionIds->count()],
                    'member_key' => hash('sha256', $email),
                    'normalized_email' => $email,
                    'display_name' => 'Member Demo '.$index,
                    'audience_type' => 'personal',
                    'status' => $index === 4 ? 'unsubscribed' : 'subscribed',
                    'subscribed_at' => $now->copy()->subDays($index),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach ([
            ['name' => 'Lead moi trong 30 ngay', 'slug' => 'lead-moi-30-ngay', 'description' => 'Lead moi phat sinh trong 30 ngay gan day.', 'rules' => [['field' => 'created_at', 'operator' => 'within', 'value' => 30]], 'is_automatic' => true],
            ['name' => 'Quan tam dich vu Business', 'slug' => 'quan-tam-dich-vu-business', 'description' => 'Nhom khach hang quan tam goi Business Pro.', 'rules' => [['field' => 'service_interest', 'operator' => 'equals', 'value' => 'business-pro']], 'is_automatic' => false],
        ] as $segment) {
            $rules = $segment['rules'];
            $segment['rules'] = json_encode($rules);
            DB::table('marketing_segments')->insert($segment + ['status' => 'active', 'created_by' => $adminId, 'last_evaluated_at' => $now->copy()->subHour(), 'created_at' => $now, 'updated_at' => $now]);
        }
    }
}
