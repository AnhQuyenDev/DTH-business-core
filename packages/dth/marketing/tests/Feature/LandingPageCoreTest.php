<?php

namespace Dth\Marketing\Tests\Feature;

use Dth\Marketing\Contracts\CatalogProvider;
use Dth\Marketing\DTO\CatalogPackageData;
use Dth\Marketing\DTO\CatalogServiceData;
use Dth\Marketing\Enums\FormAudienceType;
use Dth\Marketing\Enums\FormFieldType;
use Dth\Marketing\Enums\FormTemplateStatus;
use Dth\Marketing\Enums\LandingPageStatus;
use Dth\Marketing\Models\FormTemplate;
use Dth\Marketing\Models\LandingPage;
use Dth\Marketing\Models\MarketingCampaign;
use Dth\Marketing\Services\CampaignServiceScopeService;
use Dth\Marketing\Services\FormTemplateLifecycleService;
use Dth\Marketing\Services\LandingPageCatalogService;
use Dth\Marketing\Services\LandingPageHtmlImportService;
use Dth\Marketing\Services\LandingPageLifecycleService;
use Dth\Marketing\Services\LandingPageRenderService;
use Dth\Marketing\Services\LandingPageUtmBuilder;
use Dth\Marketing\Tests\Fakes\FakeCatalogProvider;
use Dth\Marketing\Tests\TestCase;
use Illuminate\Validation\ValidationException;

class LandingPageCoreTest extends TestCase
{
    public function test_landing_html_import_preserves_document_styles_and_extracts_embedded_form(): void
    {
        $source = <<<'HTML'
<!doctype html>
<html>
<head>
    <title>Styled Landing</title>
    <style>body{background:#112233;color:#ffffff}.hero{padding:40px}</style>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: {} } }</script>
</head>
<body class="hero">
    <h1>Campaign</h1>
    <form id="lead-form"><input type="email" name="email" required><button>Send</button></form>
</body>
</html>
HTML;

        $import = app(LandingPageHtmlImportService::class)->prepare($source);

        $this->assertSame('Styled Landing', $import['title']);
        $this->assertStringContainsString('<style>body{background:#112233', $import['html']);
        $this->assertStringContainsString('cdn.tailwindcss.com', $import['html']);
        $this->assertStringContainsString('tailwind.config', $import['html']);
        $this->assertStringNotContainsString('<form id="lead-form">', $import['html']);
        $this->assertStringNotContainsString('{{form_personal}}', $import['html']);
        $this->assertStringNotContainsString('{{form_business}}', $import['html']);
        $this->assertStringNotContainsString('{{forms_section}}', $import['html']);
        $this->assertCount(1, $import['embedded_forms']);
        $this->assertSame('personal', $import['embedded_forms'][0]['audience_type']);
    }

    public function test_full_imported_document_is_rendered_without_generic_landing_wrapper(): void
    {
        $page = LandingPage::query()->create([
            'name' => 'Exact Preview',
            'html_body' => '<!doctype html><html><head><style>.hero{display:grid}</style></head><body><main class="hero">Exact</main></body></html>',
            'css_body' => '.hero{gap:20px}',
        ]);

        $html = app(LandingPageRenderService::class)->render($page, true);

        $this->assertStringContainsString('<main class="hero">Exact</main>', $html);
        $this->assertStringContainsString('.hero{display:grid}', $html);
        $this->assertStringContainsString('.hero{gap:20px}', $html);
        $this->assertStringNotContainsString('class="lp-shell"', $html);
        $this->assertStringContainsString('dth-marketing-preview-badge', $html);
    }

    public function test_attached_form_template_is_normalized_and_appended_in_source_parity_section(): void
    {
        // Existing V3 visual-token data must render without re-import. Source
        // parity intentionally normalizes it into the canonical runtime form.
        $template = FormTemplate::query()->create([
            'name' => 'Imported Personal Form',
            'audience_type' => FormAudienceType::Personal->value,
            'html_body' => '<section class="form-card"><form>{{control:email}}<button type="submit">Send</button></form></section>',
            'schema' => ['render_mode' => 'visual_tokens_v3'],
        ]);
        $template->fields()->create([
            'label' => 'Email',
            'field_key' => 'email',
            'field_type' => FormFieldType::Email->value,
        ]);

        $page = LandingPage::query()->create([
            'name' => 'Imported Form Landing',
            'personal_form_template_id' => $template->id,
            'html_body' => '<!doctype html><html><head></head><body><main class="hero">Before {{form_personal}} After</main></body></html>',
        ]);

        $html = app(LandingPageRenderService::class)->render($page, true);

        $this->assertStringContainsString('<main class="hero">Before  After</main>', $html);
        $this->assertStringContainsString('class="lp-forms-section"', $html);
        $this->assertStringContainsString('class="lp-form-template__form"', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringNotContainsString('{{form_personal}}', $html);
        $this->assertStringNotContainsString('dth-marketing-form-frame', $html);
    }

    public function test_publish_requires_campaign_and_both_active_form_types_and_public_route_only_serves_published(): void
    {
        $campaign = MarketingCampaign::query()->create(['name' => 'Acquisition Campaign']);
        [$personal, $business] = $this->activeForms();

        $page = LandingPage::query()->create([
            'marketing_campaign_id' => $campaign->id,
            'name' => 'VPS Landing',
            'personal_form_template_id' => $personal->id,
            'business_form_template_id' => $business->id,
            'html_body' => '<h1>Hello</h1><script>alert(1)</script><img src=x onerror=alert(1)>',
        ]);

        $this->assertSame(LandingPageStatus::Draft, $page->status);
        $this->assertStringNotContainsString('<script', (string) $page->html_body);
        $this->assertStringNotContainsString('onerror=', (string) $page->html_body);
        $this->get('/lp/'.$page->slug)->assertNotFound();

        app(LandingPageLifecycleService::class)->transition($page, LandingPageStatus::Published);
        $this->get('/lp/'.$page->slug)->assertOk();

        $utm = app(LandingPageUtmBuilder::class)->build($page, [
            'source' => 'facebook',
            'medium' => 'paid-social',
            'content' => 'hero',
        ]);
        $this->assertStringContainsString('utm_source=facebook', $utm);
        $this->assertStringContainsString('utm_medium=paid-social', $utm);
        $this->assertStringContainsString('utm_campaign=acquisition-campaign', $utm);

        try {
            $page->headline = 'Changed while published';
            $page->save();
            $this->fail('Published content must be immutable until unpublish.');
        } catch (ValidationException) {
            $page->refresh();
        }

        app(LandingPageLifecycleService::class)->transition($page, LandingPageStatus::Draft);
        $this->get('/lp/'.$page->slug)->assertNotFound();
    }

    public function test_form_template_delete_is_blocked_while_a_published_landing_page_uses_it(): void
    {
        $campaign = MarketingCampaign::query()->create(['name' => 'Published Form Guard']);
        [$personal, $business] = $this->activeForms();
        $page = LandingPage::query()->create([
            'marketing_campaign_id' => $campaign->id,
            'name' => 'Published Form Guard Landing',
            'personal_form_template_id' => $personal->id,
            'business_form_template_id' => $business->id,
        ]);
        app(LandingPageLifecycleService::class)->transition($page, LandingPageStatus::Published);

        $this->expectException(ValidationException::class);
        $personal->delete();
    }

    public function test_publish_fails_without_personal_and_business_forms(): void
    {
        $campaign = MarketingCampaign::query()->create(['name' => 'Campaign']);
        $page = LandingPage::query()->create([
            'marketing_campaign_id' => $campaign->id,
            'name' => 'Incomplete Landing',
        ]);

        $this->expectException(ValidationException::class);
        $page->status = LandingPageStatus::Published;
        $page->save();
    }

    public function test_service_interest_mapping_requires_landing_service_context(): void
    {
        $campaign = MarketingCampaign::query()->create(['name' => 'Service Campaign']);
        [$personal, $business] = $this->activeForms(true);

        $page = LandingPage::query()->create([
            'marketing_campaign_id' => $campaign->id,
            'name' => 'Service Landing',
            'personal_form_template_id' => $personal->id,
            'business_form_template_id' => $business->id,
        ]);

        $this->expectException(ValidationException::class);
        app(LandingPageLifecycleService::class)->transition($page, LandingPageStatus::Published);
    }

    public function test_landing_service_and_packages_must_match_campaign_scope_via_catalog_provider(): void
    {
        $this->app->instance(CatalogProvider::class, new FakeCatalogProvider(
            [
                new CatalogServiceData('svc-vps', 'VPS', 'VPS', true),
                new CatalogServiceData('svc-cloud', 'Cloud', 'CLOUD', true),
            ],
            [
                new CatalogPackageData('pkg-vps-basic', 'svc-vps', 'VPS Basic', 'VPS-B', true),
                new CatalogPackageData('pkg-cloud-basic', 'svc-cloud', 'Cloud Basic', 'CLOUD-B', true),
            ],
        ));
        $this->app->forgetInstance(CampaignServiceScopeService::class);
        $this->app->forgetInstance(LandingPageCatalogService::class);

        $campaign = MarketingCampaign::query()->create([
            'name' => 'VPS Only Campaign',
            'service_references' => ['svc-vps'],
        ]);
        [$personal, $business] = $this->activeForms();

        $page = LandingPage::query()->create([
            'marketing_campaign_id' => $campaign->id,
            'name' => 'Valid VPS Landing',
            'service_reference' => 'svc-vps',
            'package_references' => ['pkg-vps-basic'],
            'personal_form_template_id' => $personal->id,
            'business_form_template_id' => $business->id,
        ]);

        $this->assertSame('VPS', data_get($page->catalog_snapshot, 'service.name'));

        $this->expectException(ValidationException::class);
        LandingPage::query()->create([
            'marketing_campaign_id' => $campaign->id,
            'name' => 'Out of Scope Landing',
            'service_reference' => 'svc-cloud',
            'package_references' => ['pkg-cloud-basic'],
            'personal_form_template_id' => $personal->id,
            'business_form_template_id' => $business->id,
        ]);
    }

    public function test_new_landing_page_cannot_bypass_draft_status(): void
    {
        $this->expectException(ValidationException::class);

        LandingPage::query()->create([
            'name' => 'Bypass Landing',
            'status' => LandingPageStatus::Published->value,
        ]);
    }

    /** @return array{0: FormTemplate, 1: FormTemplate} */
    private function activeForms(bool $personalMapsService = false): array
    {
        $lifecycle = app(FormTemplateLifecycleService::class);

        $personal = FormTemplate::query()->create([
            'name' => 'Personal Form '.uniqid('', true),
            'audience_type' => FormAudienceType::Personal->value,
        ]);
        $personal->fields()->create([
            'label' => 'Email',
            'field_key' => 'email',
            'field_type' => FormFieldType::Email->value,
            'contact_mapping' => $personalMapsService ? 'lead.service_interest' : 'lead.email',
        ]);
        $lifecycle->transition($personal, FormTemplateStatus::Active);

        $business = FormTemplate::query()->create([
            'name' => 'Business Form '.uniqid('', true),
            'audience_type' => FormAudienceType::Business->value,
        ]);
        $business->fields()->create([
            'label' => 'Company',
            'field_key' => 'company_name',
            'field_type' => FormFieldType::Text->value,
            'contact_mapping' => 'lead.company_name',
        ]);
        $lifecycle->transition($business, FormTemplateStatus::Active);

        return [$personal, $business];
    }
}
