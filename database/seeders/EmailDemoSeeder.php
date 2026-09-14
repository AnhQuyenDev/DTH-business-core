<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EmailDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $adminId = DB::table('users')->where('email', 'admin@dth.local')->value('id');

        $domains = collect([
            ['domain' => 'mail.dth.local', 'status' => 'verified', 'spf_status' => 'verified', 'dkim_status' => 'verified', 'dmarc_status' => 'pending', 'dkim_selector' => 'dth', 'notes' => 'Domaine demo giao dich'],
            ['domain' => 'campaigns.dth.local', 'status' => 'pending', 'spf_status' => 'pending', 'dkim_status' => 'pending', 'dmarc_status' => 'pending', 'dkim_selector' => 'campaign', 'notes' => 'Domaine demo marketing'],
        ])->map(function (array $domain) use ($now): array {
            return $domain + ['created_at' => $now, 'updated_at' => $now];
        });
        DB::table('email_sending_domains')->insert($domains->all());
        $domainIds = DB::table('email_sending_domains')->orderBy('id')->pluck('id');

        $accountRows = [
            ['sending_domain_id' => $domainIds[0], 'name' => 'DTH Transactional', 'from_name' => 'DTH Business', 'from_email' => 'hello@mail.dth.local', 'reply_to' => 'support@dth.local', 'daily_limit' => 5000, 'hourly_limit' => 500, 'status' => 'active'],
            ['sending_domain_id' => $domainIds[1], 'name' => 'DTH Marketing', 'from_name' => 'DTH Marketing', 'from_email' => 'campaigns@campaigns.dth.local', 'reply_to' => 'marketing@dth.local', 'daily_limit' => 10000, 'hourly_limit' => 1000, 'status' => 'active'],
        ];
        foreach ($accountRows as $account) {
            DB::table('email_sending_accounts')->insert($account + [
                'provider' => 'smtp',
                'encrypted_config' => Crypt::encryptString(json_encode(['smtp_host' => 'smtp.dth.local', 'smtp_port' => 587, 'smtp_scheme' => 'tls', 'smtp_timeout' => 10])),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        $accountIds = DB::table('email_sending_accounts')->orderBy('id')->pluck('id');

        foreach ([
            ['name' => 'Giao dich', 'slug' => 'transactional', 'description' => 'Email he thong va thong bao giao dich.'],
            ['name' => 'Marketing', 'slug' => 'marketing', 'description' => 'Email thu hut va cham soc khach hang.'],
            ['name' => 'Ban tin', 'slug' => 'newsletter', 'description' => 'Ban tin dinh ky cho khach hang.'],
        ] as $category) {
            DB::table('email_template_categories')->insert($category + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }
        $categoryIds = DB::table('email_template_categories')->orderBy('id')->pluck('id');

        $templateRows = [
            ['name' => 'Welcome to DTH', 'subject' => 'Chao mung ban den voi DTH', 'preheader' => 'Bat dau hanh trinh cung DTH.', 'category_id' => $categoryIds[0], 'status' => 'active'],
            ['name' => 'Lead follow-up', 'subject' => 'DTH da nhan yeu cau cua ban', 'preheader' => 'Chung toi se lien he trong thoi gian som nhat.', 'category_id' => $categoryIds[1], 'status' => 'active'],
            ['name' => 'Monthly newsletter', 'subject' => 'Ban tin DTH thang nay', 'preheader' => 'Cap nhat moi tu DTH Business.', 'category_id' => $categoryIds[2], 'status' => 'draft'],
        ];
        foreach ($templateRows as $template) {
            DB::table('email_templates')->insert($template + [
                'html_body' => '<h1>{{name}}</h1><p>Cam on ban da quan tam den DTH Business.</p>',
                'text_body' => 'Cam on ban da quan tam den DTH Business.',
                'created_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        $templateIds = DB::table('email_templates')->orderBy('id')->pluck('id');

        $campaignRows = [
            ['name' => 'Chao mung khach hang moi', 'subject' => 'Chao mung ban den voi DTH', 'email_template_id' => $templateIds[0], 'sending_account_id' => $accountIds[0], 'status' => 'completed', 'started_at' => $now->copy()->subDays(2), 'completed_at' => $now->copy()->subDays(2)],
            ['name' => 'Follow-up Lead thang 9', 'subject' => 'DTH co the ho tro gi cho ban?', 'email_template_id' => $templateIds[1], 'sending_account_id' => $accountIds[1], 'status' => 'scheduled', 'scheduled_at' => $now->copy()->addDay()],
        ];
        foreach ($campaignRows as $campaign) {
            DB::table('email_campaigns')->insert($campaign + ['created_by' => $adminId, 'created_at' => $now, 'updated_at' => $now]);
        }
        $campaignIds = DB::table('email_campaigns')->orderBy('id')->pluck('id');

        foreach ($campaignIds as $index => $campaignId) {
            foreach (range(1, 5) as $recipient) {
                DB::table('email_campaign_recipients')->insert([
                    'campaign_id' => $campaignId,
                    'email' => "demo{$index}_{$recipient}@example.com",
                    'name' => 'Khach hang demo '.$recipient,
                    'status' => $index === 0 && $recipient <= 3 ? 'opened' : 'pending',
                    'variables' => json_encode(['company' => 'DTH Demo']),
                    'sent_at' => $index === 0 ? $now->copy()->subDays(2) : null,
                    'opened_at' => $index === 0 && $recipient <= 3 ? $now->copy()->subDay() : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
