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

        $campaignIds = collect([
            ['name' => 'Q3 Lead Acquisition', 'slug' => 'q3-lead-acquisition', 'description' => 'Acquisition campaign for new business leads.', 'status' => 'active', 'start_date' => $now->copy()->subDays(20)->toDateString(), 'end_date' => $now->copy()->addDays(25)->toDateString(), 'budget' => 45000000, 'currency' => 'VND'],
            ['name' => 'Business Pro Launch', 'slug' => 'business-pro-launch', 'description' => 'Launch campaign for Business Pro package.', 'status' => 'draft', 'start_date' => $now->copy()->addDays(7)->toDateString(), 'end_date' => $now->copy()->addDays(60)->toDateString(), 'budget' => 28000000, 'currency' => 'VND'],
            ['name' => 'Customer Retention', 'slug' => 'customer-retention', 'description' => 'Retention campaign for active customers.', 'status' => 'paused', 'start_date' => $now->copy()->subDays(60)->toDateString(), 'end_date' => $now->copy()->subDays(5)->toDateString(), 'budget' => 15000, 'currency' => 'USD'],
            ['name' => 'Completed Webinar', 'slug' => 'completed-webinar', 'description' => 'Completed webinar acquisition campaign.', 'status' => 'completed', 'start_date' => $now->copy()->subDays(90)->toDateString(), 'end_date' => $now->copy()->subDays(70)->toDateString(), 'budget' => 12000000, 'currency' => 'VND'],
        ])->map(function (array $row) use ($now, $adminId): int {
            return DB::table('marketing_campaigns')->insertGetId($row + ['created_by' => $adminId, 'context' => json_encode(['channel' => 'web', 'demo' => true]), 'created_at' => $now, 'updated_at' => $now]);
        });

        $formIds = collect([
            ['name' => 'Personal consultation form', 'slug' => 'personal-consultation-form', 'description' => 'Collect personal consultation requests.', 'audience_type' => 'personal', 'status' => 'active', 'submit_button_text' => 'Request consultation', 'success_message' => 'Thank you. We will contact you soon.'],
            ['name' => 'Business registration form', 'slug' => 'business-registration-form', 'description' => 'Collect business discovery requests.', 'audience_type' => 'business', 'status' => 'active', 'submit_button_text' => 'Book a demo', 'success_message' => 'Your business request has been received.'],
            ['name' => 'Archived partner form', 'slug' => 'archived-partner-form', 'description' => 'Historical partner acquisition form.', 'audience_type' => 'business', 'status' => 'archived', 'submit_button_text' => 'Submit', 'success_message' => null],
        ])->map(function (array $row) use ($now, $adminId): int {
            return DB::table('marketing_form_templates')->insertGetId($row + [
                'version' => 1, 'html_body' => '<form><input name="email"><button type="submit">Submit</button></form>', 'schema' => json_encode(['version' => 1, 'layout' => 'stacked']),
                'auto_tag_names' => json_encode(['demo']), 'auto_list_names' => json_encode(['demo-audience']), 'auto_create_tags' => true, 'auto_create_lists' => true, 'auto_create_segment' => false,
                'created_by' => $adminId, 'created_at' => $now, 'updated_at' => $now,
            ]);
        });

        $fieldBlueprints = [
            ['label' => 'Full name', 'key' => 'full_name', 'type' => 'text', 'mapping' => 'contact.display_name', 'required' => true],
            ['label' => 'Email', 'key' => 'email', 'type' => 'email', 'mapping' => 'contact.email', 'required' => true],
            ['label' => 'Phone', 'key' => 'phone', 'type' => 'phone', 'mapping' => 'contact.phone', 'required' => false],
            ['label' => 'Service interest', 'key' => 'service_interest', 'type' => 'select', 'mapping' => 'lead.service_interest', 'required' => false],
            ['label' => 'Message', 'key' => 'message', 'type' => 'textarea', 'mapping' => null, 'required' => false],
            ['label' => 'Preferred date', 'key' => 'preferred_date', 'type' => 'date', 'mapping' => null, 'required' => false],
        ];
        foreach ($formIds as $formId) {
            foreach ($fieldBlueprints as $sort => $field) {
                DB::table('marketing_form_fields')->insert([
                    'form_template_id' => $formId, 'label' => $field['label'], 'field_key' => $field['key'], 'field_type' => $field['type'], 'placeholder' => $field['label'],
                    'options' => $field['type'] === 'select' ? json_encode(['crm', 'business-pro', 'email']) : null, 'is_required' => $field['required'], 'contact_mapping' => $field['mapping'], 'sort_order' => $sort,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        $landingIds = collect([
            ['name' => 'Personal consultation landing', 'slug' => 'personal-consultation', 'marketing_campaign_id' => $campaignIds[0], 'personal_form_template_id' => $formIds[0], 'business_form_template_id' => $formIds[1], 'status' => 'published', 'headline' => 'Find the right solution for you', 'subheadline' => 'Talk to the DTH team about your goals.', 'cta_text' => 'Request consultation'],
            ['name' => 'Business Pro landing', 'slug' => 'business-pro', 'marketing_campaign_id' => $campaignIds[1], 'personal_form_template_id' => $formIds[0], 'business_form_template_id' => $formIds[1], 'status' => 'draft', 'headline' => 'Accelerate your business operations', 'subheadline' => 'Connect CRM, Marketing and Email.', 'cta_text' => 'Book a demo'],
            ['name' => 'Retention landing', 'slug' => 'customer-retention', 'marketing_campaign_id' => $campaignIds[2], 'personal_form_template_id' => $formIds[0], 'business_form_template_id' => $formIds[1], 'status' => 'archived', 'headline' => 'Grow with DTH', 'subheadline' => null, 'cta_text' => 'Learn more'],
        ])->map(function (array $row) use ($now, $adminId): int {
            return DB::table('marketing_landing_pages')->insertGetId($row + [
                'page_title' => $row['name'].' | DTH Business', 'content' => '<p>Demo landing page content for DTH Business.</p>', 'html_body' => '<section><h1>'.$row['headline'].'</h1></section>', 'css_body' => '.hero{padding:40px}', 'theme_tokens' => json_encode(['accent' => '#f59e0b']),
                'tracking_source' => 'demo-seeder', 'auto_tag_names' => json_encode(['landing-demo']), 'auto_list_names' => json_encode(['demo-audience']), 'auto_create_tags' => true, 'auto_create_lists' => true, 'auto_create_segment' => false,
                'published_at' => $row['status'] === 'published' ? $now->copy()->subDays(4) : null, 'created_by' => $adminId, 'created_at' => $now, 'updated_at' => $now,
            ]);
        });

        foreach ($landingIds as $landingIndex => $landingId) {
            for ($index = 1; $index <= 8; $index++) {
                $email = "marketing.{$landingIndex}.{$index}@example.com";
                $status = ['received', 'processed', 'failed', 'spam'][($index + $landingIndex) % 4];
                DB::table('marketing_landing_page_views')->insert([
                    'landing_page_id' => $landingId, 'marketing_campaign_id' => $campaignIds[$landingIndex], 'session_id' => Str::random(32), 'ip_address' => '192.0.2.'.($index + 10), 'user_agent' => 'Demo Browser', 'referrer' => 'https://example.com/ads',
                    'utm_source' => ['facebook', 'google', 'newsletter'][$index % 3], 'utm_medium' => ['paid_social', 'cpc', 'email'][$index % 3], 'utm_campaign' => 'demo-campaign', 'utm_content' => 'variant-'.$index, 'utm_term' => 'business', 'viewed_at' => $now->copy()->subDays($index), 'created_at' => $now, 'updated_at' => $now,
                ]);
                DB::table('marketing_landing_page_submissions')->insert([
                    'landing_page_id' => $landingId, 'marketing_campaign_id' => $campaignIds[$landingIndex], 'form_template_id' => $landingIndex === 1 ? $formIds[1] : $formIds[0], 'submission_token' => (string) Str::uuid(), 'payload_fingerprint' => hash('sha256', $email.'|'.$index), 'member_key' => hash('sha256', $email), 'submission_type' => $landingIndex === 1 ? 'business' : 'personal',
                    'data' => json_encode(['full_name' => 'Marketing Lead '.$index, 'email' => $email, 'phone' => '0908000'.str_pad((string) $index, 3, '0', STR_PAD_LEFT), 'message' => 'Demo request']), 'normalized_data' => json_encode(['display_name' => 'Marketing Lead '.$index, 'email' => $email]), 'normalized_email' => $email, 'normalized_phone' => '0908000'.str_pad((string) $index, 3, '0', STR_PAD_LEFT), 'display_name' => 'Marketing Lead '.$index, 'company_name' => $landingIndex === 1 ? 'Demo Company '.$index : null,
                    'service_reference' => 'business-pro', 'contact_reference' => 'CNT-DEMO-'.str_pad((string) (($index % 6) + 1), 2, '0', STR_PAD_LEFT), 'lead_reference' => 'LEAD-DEMO-'.str_pad((string) (($index % 8) + 1), 2, '0', STR_PAD_LEFT), 'lead_code' => 'LEAD-DEMO-'.str_pad((string) (($index % 8) + 1), 2, '0', STR_PAD_LEFT), 'lead_status' => $status === 'processed' ? 'active' : null,
                    'status' => $status, 'contact_action' => $status === 'processed' ? 'created' : null, 'source' => 'landing_page', 'ip_address' => '192.0.2.'.($index + 20), 'user_agent' => 'Demo Browser', 'referrer' => 'https://example.com/ads', 'utm_source' => 'facebook', 'utm_medium' => 'paid_social', 'utm_campaign' => 'q3-lead-acquisition', 'utm_content' => 'creative-'.$index, 'utm_term' => 'crm', 'submitted_at' => $now->copy()->subDays($index), 'processed_at' => $status === 'processed' ? $now->copy()->subDays($index - 1) : null, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        $submissionIds = DB::table('marketing_landing_page_submissions')->orderBy('id')->pluck('id');
        $listIds = collect([
            ['name' => 'Personal prospects', 'slug' => 'personal-prospects', 'type' => 'newsletter', 'status' => 'active'],
            ['name' => 'Business prospects', 'slug' => 'business-prospects', 'type' => 'segment', 'status' => 'active'],
            ['name' => 'Unsubscribed archive', 'slug' => 'unsubscribed-archive', 'type' => 'export', 'status' => 'inactive'],
        ])->map(function (array $row) use ($now, $adminId): int {
            return DB::table('marketing_contact_lists')->insertGetId($row + ['description' => 'Demo audience list for interface preview.', 'created_by' => $adminId, 'created_at' => $now, 'updated_at' => $now]);
        });
        foreach ($listIds as $listIndex => $listId) {
            foreach (range(1, 12) as $index) {
                $email = "member.{$listIndex}.{$index}@example.com";
                DB::table('marketing_contact_list_members')->insert([
                    'contact_list_id' => $listId, 'source_submission_id' => $submissionIds[($index - 1) % $submissionIds->count()], 'member_key' => hash('sha256', $email), 'contact_reference' => 'CNT-DEMO-'.str_pad((string) (($index % 6) + 1), 2, '0', STR_PAD_LEFT), 'normalized_email' => $email, 'normalized_phone' => '0919000'.str_pad((string) $index, 3, '0', STR_PAD_LEFT), 'display_name' => 'Audience Member '.$index, 'audience_type' => $listIndex === 1 ? 'business' : 'personal', 'status' => $index % 5 === 0 ? 'unsubscribed' : 'subscribed', 'metadata' => json_encode(['source' => 'demo']), 'subscribed_at' => $now->copy()->subDays($index), 'unsubscribed_at' => $index % 5 === 0 ? $now->copy()->subDays(1) : null, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        foreach ([
            ['name' => 'New leads last 30 days', 'slug' => 'new-leads-last-30-days', 'rules' => [['field' => 'created_at', 'operator' => 'within', 'value' => 30]], 'is_automatic' => true, 'status' => 'active'],
            ['name' => 'Business service interest', 'slug' => 'business-service-interest', 'rules' => [['field' => 'service_interest', 'operator' => 'equals', 'value' => 'business-pro']], 'is_automatic' => false, 'status' => 'active'],
            ['name' => 'Archived campaign segment', 'slug' => 'archived-campaign-segment', 'rules' => [], 'is_automatic' => false, 'status' => 'inactive'],
        ] as $segment) {
            $rules = $segment['rules'];
            $segment['rules'] = json_encode($rules);
            DB::table('marketing_segments')->insert($segment + ['description' => 'Demo segment for filters and analytics.', 'created_by' => $adminId, 'last_evaluated_at' => $now->copy()->subHours(2), 'created_at' => $now, 'updated_at' => $now]);
        }

        if (DB::getSchemaBuilder()->hasTable('marketing_landing_page_utm_urls')) {
            foreach ($landingIds as $landingId) {
                DB::table('marketing_landing_page_utm_urls')->insert(['landing_page_id' => $landingId, 'marketing_campaign_id' => $campaignIds[0], 'name' => 'Facebook demo link', 'utm_source' => 'facebook', 'utm_medium' => 'paid_social', 'utm_campaign' => 'q3-lead-acquisition', 'utm_content' => 'hero', 'utm_term' => 'crm', 'url' => 'https://demo.dth.local/landing?utm_source=facebook&utm_medium=paid_social&utm_campaign=q3-lead-acquisition', 'created_by' => $adminId, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }
}
