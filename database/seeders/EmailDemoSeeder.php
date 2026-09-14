<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmailDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $adminId = DB::table('users')->where('email', 'admin@dth.local')->value('id');

        $domainIds = collect([
            ['domain' => 'mail.dth.local', 'status' => 'verified', 'spf_status' => 'verified', 'dkim_status' => 'verified', 'dmarc_status' => 'verified', 'dkim_selector' => 'dth', 'notes' => 'Transactional email domain'],
            ['domain' => 'campaigns.dth.local', 'status' => 'pending', 'spf_status' => 'verified', 'dkim_status' => 'pending', 'dmarc_status' => 'pending', 'dkim_selector' => 'campaign', 'notes' => 'Marketing campaign domain'],
            ['domain' => 'notify.dth.local', 'status' => 'pending', 'spf_status' => 'pending', 'dkim_status' => 'pending', 'dmarc_status' => 'pending', 'dkim_selector' => 'notify', 'notes' => null],
        ])->map(function (array $row) use ($now): int {
            return DB::table('email_sending_domains')->insertGetId($row + ['created_at' => $now, 'updated_at' => $now]);
        });

        $accountIds = collect([
            ['sending_domain_id' => $domainIds[0], 'name' => 'DTH Transactional', 'from_name' => 'DTH Business', 'from_email' => 'hello@mail.dth.local', 'reply_to' => 'support@dth.local', 'status' => 'active', 'daily_limit' => 5000, 'hourly_limit' => 500],
            ['sending_domain_id' => $domainIds[1], 'name' => 'DTH Marketing', 'from_name' => 'DTH Marketing', 'from_email' => 'campaigns@campaigns.dth.local', 'reply_to' => 'marketing@dth.local', 'status' => 'active', 'daily_limit' => 10000, 'hourly_limit' => 1000],
            ['sending_domain_id' => $domainIds[2], 'name' => 'DTH Backup', 'from_name' => 'DTH Support', 'from_email' => 'notify@notify.dth.local', 'reply_to' => null, 'status' => 'inactive', 'daily_limit' => 1000, 'hourly_limit' => 100],
        ])->map(function (array $row) use ($now): int {
            return DB::table('email_sending_accounts')->insertGetId($row + [
                'provider' => 'smtp',
                'encrypted_config' => Crypt::encryptString(json_encode(['smtp_host' => 'smtp.dth.local', 'smtp_port' => 587, 'smtp_username' => 'demo', 'smtp_scheme' => 'tls', 'smtp_timeout' => 10])),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        $categoryIds = collect([
            ['name' => 'Transactional', 'slug' => 'transactional', 'description' => 'System notifications and transaction messages.'],
            ['name' => 'Marketing', 'slug' => 'marketing', 'description' => 'Campaign and lead nurturing messages.'],
            ['name' => 'Newsletter', 'slug' => 'newsletter', 'description' => 'Recurring customer updates.'],
            ['name' => 'Internal', 'slug' => 'internal', 'description' => 'Internal team notifications.', 'is_active' => false],
        ])->map(function (array $row) use ($now): int {
            return DB::table('email_template_categories')->insertGetId($row + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        });

        $templateIds = collect([
            ['name' => 'Welcome email', 'subject' => 'Welcome to DTH Business', 'preheader' => 'Your DTH journey starts here.', 'category_id' => $categoryIds[0], 'status' => 'active'],
            ['name' => 'Lead follow-up', 'subject' => 'How can DTH help your business?', 'preheader' => 'A quick follow-up from our team.', 'category_id' => $categoryIds[1], 'status' => 'active'],
            ['name' => 'Monthly newsletter', 'subject' => 'DTH Business monthly update', 'preheader' => 'Product and service updates.', 'category_id' => $categoryIds[2], 'status' => 'active'],
            ['name' => 'Legacy promotion', 'subject' => 'Previous DTH promotion', 'preheader' => null, 'category_id' => $categoryIds[1], 'status' => 'inactive'],
            ['name' => 'Internal onboarding', 'subject' => 'New staff onboarding', 'preheader' => null, 'category_id' => $categoryIds[3], 'status' => 'draft'],
        ])->map(function (array $row) use ($now, $adminId): int {
            return DB::table('email_templates')->insertGetId($row + [
                'html_body' => '<html><body><h1>{{ name }}</h1><p>Thank you for choosing DTH Business.</p><a href="{{ action_url }}">View details</a></body></html>',
                'text_body' => 'Thank you for choosing DTH Business. View details: {{ action_url }}',
                'created_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        $campaignIds = collect([
            ['name' => 'Welcome series', 'subject' => 'Welcome to DTH', 'email_template_id' => $templateIds[0], 'sending_account_id' => $accountIds[0], 'status' => 'completed', 'started_at' => $now->copy()->subDays(12), 'completed_at' => $now->copy()->subDays(11)],
            ['name' => 'September lead follow-up', 'subject' => 'Your DTH consultation', 'email_template_id' => $templateIds[1], 'sending_account_id' => $accountIds[1], 'status' => 'scheduled', 'scheduled_at' => $now->copy()->addDays(2)],
            ['name' => 'Customer newsletter', 'subject' => 'DTH September newsletter', 'email_template_id' => $templateIds[2], 'sending_account_id' => $accountIds[1], 'status' => 'draft'],
            ['name' => 'Failed delivery test', 'subject' => 'Delivery failure example', 'email_template_id' => $templateIds[1], 'sending_account_id' => $accountIds[0], 'status' => 'failed', 'failed_at' => $now->copy()->subDay()],
        ])->map(function (array $row) use ($now, $adminId): int {
            return DB::table('email_campaigns')->insertGetId($row + ['created_by' => $adminId, 'created_at' => $now, 'updated_at' => $now]);
        });

        $recipientStates = ['opened', 'clicked', 'delivered', 'sent', 'failed', 'pending'];
        foreach ($campaignIds as $campaignIndex => $campaignId) {
            foreach (range(1, 12) as $index) {
                $state = $recipientStates[($campaignIndex * 3 + $index) % count($recipientStates)];
                $sentAt = in_array($state, ['sent', 'delivered', 'opened', 'clicked'], true) ? $now->copy()->subDays(2) : null;
                $openedAt = in_array($state, ['opened', 'clicked'], true) ? $now->copy()->subDay() : null;
                DB::table('email_campaign_recipients')->insert([
                    'campaign_id' => $campaignId,
                    'email' => "subscriber.{$campaignIndex}.{$index}@example.com",
                    'name' => 'Demo Recipient '.$index,
                    'status' => $state,
                    'variables' => json_encode(['name' => 'Demo Recipient '.$index, 'company' => 'Demo Company']),
                    'sent_at' => $sentAt,
                    'opened_at' => $openedAt,
                    'clicked_at' => $state === 'clicked' ? $now->copy()->subHours(12) : null,
                    'failed_at' => $state === 'failed' ? $now->copy()->subHours(4) : null,
                    'failure_reason' => $state === 'failed' ? 'Mailbox rejected the message.' : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $recipients = DB::table('email_campaign_recipients')->whereIn('campaign_id', $campaignIds->all())->orderBy('id')->get();
        foreach ($recipients->take(18) as $recipient) {
            $messageStatus = in_array($recipient->status, ['failed'], true) ? 'failed' : (in_array($recipient->status, ['opened', 'clicked', 'delivered'], true) ? 'delivered' : 'queued');
            $messageId = DB::table('email_messages')->insertGetId([
                'uuid' => (string) Str::uuid(), 'sending_account_id' => $accountIds[0], 'template_id' => $templateIds[0], 'campaign_recipient_id' => $recipient->id,
                'from_name' => 'DTH Business', 'from_email' => 'hello@mail.dth.local', 'reply_to' => 'support@dth.local', 'recipient_email' => $recipient->email, 'recipient_name' => $recipient->name,
                'subject' => 'DTH demo message', 'html_body' => '<p>Demo message</p>', 'text_body' => 'Demo message', 'status' => $messageStatus,
                'provider_message_id' => $messageStatus === 'failed' ? null : 'provider-'.$recipient->id, 'idempotency_key' => 'demo-message-'.$recipient->id, 'tracking_token' => (string) Str::uuid(), 'unsubscribe_token' => (string) Str::uuid(),
                'queued_at' => $now->copy()->subDays(2), 'sent_at' => $messageStatus !== 'queued' ? $now->copy()->subDays(2) : null, 'delivered_at' => $messageStatus === 'delivered' ? $now->copy()->subDay() : null,
                'failed_at' => $messageStatus === 'failed' ? $now->copy()->subDay() : null, 'failure_reason' => $messageStatus === 'failed' ? 'Provider rejected the address.' : null,
                'metadata' => json_encode(['demo' => true]), 'created_at' => $now, 'updated_at' => $now,
            ]);
            if ($messageStatus === 'delivered') {
                DB::table('email_events')->insert(['message_id' => $messageId, 'event_type' => 'delivered', 'provider_event_id' => 'event-'.$messageId, 'payload' => json_encode(['demo' => true]), 'occurred_at' => $now->copy()->subDay(), 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }
}
