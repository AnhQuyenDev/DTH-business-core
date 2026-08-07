<?php

namespace Tests\Feature\Marketing;

use App\Models\Crm\LandingPageForm;
use App\Models\Crm\Lead;
use App\Models\Marketing\FormField;
use App\Models\Marketing\FormTemplate;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\LandingPageSubmission;
use App\Models\Marketing\MarketingCampaign;
use App\Models\Sales\Service;
use App\Models\Sales\ServicePackage;
use App\Services\Marketing\LandingPageRenderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LandingPageServiceContextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('business_flow.v2_enabled', true);
        config()->set('business_flow.company_resolution_enabled', true);
    }

    public function test_same_form_template_uses_each_landing_page_service_catalog(): void
    {
        $template = $this->makeReusablePersonalTemplate();

        [$hosting, $hostingPro] = $this->makeServiceWithPackage(
            'HOSTING',
            'Web Hosting',
            'HOSTING_PRO',
            'Trung bình',
        );
        [$vps, $vps4Gb] = $this->makeServiceWithPackage(
            'VPS',
            'VPS',
            'VPS_4GB',
            '4GB RAM',
        );

        $hostingPage = $this->makePage(
            $template,
            $hosting,
            [$hostingPro],
            'hosting-page',
        );
        $vpsPage = $this->makePage(
            $template,
            $vps,
            [$vps4Gb],
            'vps-page',
        );

        $renderer = app(LandingPageRenderService::class);
        $hostingHtml = $renderer->render($hostingPage, 'personal');
        $vpsHtml = $renderer->render($vpsPage, 'personal');

        $this->assertStringContainsString(
            'Web Hosting - Trung bình',
            $hostingHtml,
        );
        $this->assertStringNotContainsString('VPS - 4GB RAM', $hostingHtml);

        $this->assertStringContainsString('VPS - 4GB RAM', $vpsHtml);
        $this->assertStringNotContainsString(
            'Web Hosting - Trung bình',
            $vpsHtml,
        );
    }

    public function test_submission_only_accepts_packages_from_current_landing_page(): void
    {
        $template = $this->makeReusablePersonalTemplate();

        [$hosting, $hostingPro] = $this->makeServiceWithPackage(
            'HOSTING',
            'Web Hosting',
            'HOSTING_PRO',
            'Trung bình',
        );
        [$vps, $vps4Gb] = $this->makeServiceWithPackage(
            'VPS',
            'VPS',
            'VPS_4GB',
            '4GB RAM',
        );

        $this->makePage(
            $template,
            $hosting,
            [$hostingPro],
            'hosting-page',
        );
        $vpsPage = $this->makePage(
            $template,
            $vps,
            [$vps4Gb],
            'vps-page',
        );

        $this->post('/lp/'.$vpsPage->slug.'/submit', $this->payload([
            'service_interest' => 'HOSTING_PRO',
        ]))->assertSessionHasErrors('service_interest');

        $this->assertDatabaseCount('landing_page_submissions', 0);
        $this->assertDatabaseCount('leads', 0);

        $this->post('/lp/'.$vpsPage->slug.'/submit', $this->payload([
            '_submission_token' => (string) Str::uuid(),
            'service_interest' => 'VPS_4GB',
        ]))->assertSessionHasNoErrors();

        $lead = Lead::query()->firstOrFail();

        $this->assertSame('landing_page', $lead->source);
        $this->assertSame('Landing Page', Lead::sourceLabel($lead->source));
        $this->assertSame('VPS_4GB', $lead->service_interest);
        $this->assertSame(
            'VPS - 4GB RAM',
            data_get($lead->metadata, 'service_interest_label'),
        );
        $this->assertSame(
            $vps->id,
            data_get($lead->metadata, 'service_context.service_id'),
        );
        $this->assertSame(
            $vps4Gb->id,
            data_get($lead->metadata, 'service_context.service_package_id'),
        );
        $this->assertSame(
            'VPS_4GB',
            data_get($lead->metadata, 'service_context.service_package_code'),
        );
    }

    public function test_submission_snapshots_ads_campaign_and_rejects_runtime_scope_mismatch(): void
    {
        $template = $this->makeReusablePersonalTemplate();

        [$hosting, $hostingPro] = $this->makeServiceWithPackage(
            'HOSTING',
            'Web Hosting',
            'HOSTING_PRO',
            'Trung bình',
        );
        [$vps, $vps4Gb] = $this->makeServiceWithPackage(
            'VPS',
            'VPS',
            'VPS_4GB',
            '4GB RAM',
        );

        $campaign = MarketingCampaign::query()->create([
            'name' => 'Hosting Siêu Tốc Q3/2026',
            'slug' => 'hosting-sieu-toc-q3-2026-'.uniqid(),
            'status' => 'active',
        ]);
        $campaign->services()->sync([$hosting->id]);

        $page = $this->makePage(
            $template,
            $hosting,
            [$hostingPro],
            'hosting-campaign-page',
        );
        $page->update(['marketing_campaign_id' => $campaign->id]);

        $this->post('/lp/'.$page->slug.'/submit', $this->payload([
            '_submission_token' => (string) Str::uuid(),
            'email' => 'campaign-snapshot@example.com',
            'service_interest' => 'HOSTING_PRO',
        ]))->assertSessionHasNoErrors();

        $submission = LandingPageSubmission::query()->firstOrFail();
        $lead = Lead::query()->firstOrFail();

        $this->assertSame($campaign->id, $submission->marketing_campaign_id);
        $this->assertSame(
            $campaign->id,
            data_get($lead->metadata, 'marketing_campaign_id'),
        );
        $this->assertSame(
            'Hosting Siêu Tốc Q3/2026',
            data_get($lead->metadata, 'marketing_campaign_name'),
        );

        // Mô phỏng dữ liệu bị sửa ngoài UI: đổi service của LP sang VPS nhưng
        // Campaign vẫn chỉ được phép quảng bá Hosting. Runtime phải chặn submit.
        $page->update(['service_id' => $vps->id]);
        $page->servicePackages()->sync([$vps4Gb->id]);

        $this->post('/lp/'.$page->slug.'/submit', $this->payload([
            '_submission_token' => (string) Str::uuid(),
            'email' => 'campaign-scope-invalid@example.com',
            'service_interest' => 'VPS_4GB',
        ]))->assertSessionHasErrors('service_id');

        $this->assertDatabaseCount('landing_page_submissions', 1);
        $this->assertDatabaseCount('leads', 1);
    }

    private function makeReusablePersonalTemplate(): FormTemplate
    {
        $template = FormTemplate::query()->create([
            'name' => 'Form tư vấn cá nhân dùng chung',
            'slug' => 'reusable-personal-'.uniqid(),
            'audience_type' => 'personal',
            'status' => 'active',
            'submit_button_text' => 'Đăng ký tư vấn',
        ]);

        $fields = [
            ['Họ', 'first_name', 'text', 'personal.first_name'],
            ['Tên', 'last_name', 'text', 'personal.last_name'],
            ['Email', 'email', 'email', 'personal.email'],
            ['Số điện thoại', 'phone', 'phone', 'personal.phone'],
            [
                'Dịch vụ quan tâm',
                'service_interest',
                'select',
                'lead.service_interest',
            ],
        ];

        foreach ($fields as $index => [$label, $key, $type, $mapping]) {
            FormField::query()->create([
                'landing_form_template_id' => $template->id,
                'label' => $label,
                'field_key' => $key,
                'field_type' => $type,
                'options' => $mapping === 'lead.service_interest' ? [] : null,
                'is_required' => true,
                'contact_mapping' => $mapping,
                'sort_order' => $index + 1,
                'position' => $index + 1,
            ]);
        }

        return $template;
    }

    /** @return array{0: Service, 1: ServicePackage} */
    private function makeServiceWithPackage(
        string $serviceCode,
        string $serviceName,
        string $packageCode,
        string $packageName,
    ): array {
        $service = Service::query()->create([
            'service_code' => $serviceCode,
            'name' => $serviceName,
            'slug' => Str::slug($serviceCode).'-'.uniqid(),
            'status' => 'active',
            'sort_order' => 0,
        ]);

        $package = ServicePackage::query()->create([
            'service_id' => $service->id,
            'package_code' => $packageCode,
            'name' => $packageName,
            'audience_type' => 'both',
            'status' => 'active',
            'sort_order' => 0,
        ]);

        return [$service, $package];
    }

    /**
     * @param array<int, ServicePackage> $packages
     */
    private function makePage(
        FormTemplate $template,
        Service $service,
        array $packages,
        string $slugPrefix,
    ): LandingPage {
        $page = LandingPage::query()->create([
            'name' => $service->name.' Test Page',
            'slug' => $slugPrefix.'-'.uniqid(),
            'status' => 'published',
            'published_at' => now(),
            'service_id' => $service->id,
        ]);

        $page->servicePackages()->sync(
            collect($packages)->pluck('id')->all()
        );

        LandingPageForm::query()->create([
            'landing_page_id' => $page->id,
            'form_template_id' => $template->id,
            'form_type' => 'personal',
            'display_mode' => 'embedded',
            'position_key' => 'end',
            'is_default' => true,
            'sort_order' => 0,
            'status' => 'active',
        ]);

        return $page->fresh(['service', 'servicePackages', 'forms.formTemplate']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            '_token' => csrf_token(),
            '_submission_token' => (string) Str::uuid(),
            'submission_type' => 'personal',
            'first_name' => 'Lê',
            'last_name' => 'Thu Hà',
            'email' => 'service-context@example.com',
            'phone' => '0900001234',
            'service_interest' => 'VPS_4GB',
        ], $overrides);
    }
}
