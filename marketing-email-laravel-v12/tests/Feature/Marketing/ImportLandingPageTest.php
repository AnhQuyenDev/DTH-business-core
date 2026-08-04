<?php

namespace Tests\Feature\Marketing;

use App\Models\Marketing\LandingPage;
use App\Models\User;
use App\Services\Marketing\LandingPageImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportLandingPageTest extends TestCase
{
    use RefreshDatabase;

    private LandingPageImportService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(LandingPageImportService::class);
        $this->user = User::factory()->create();
    }

    public function test_it_removes_old_form_sections_from_landing_page(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <style>
        :root { --primary: #7c3aed; --bg: #f5f3ff; }
    </style>
</head>
<body>
    <section class="hero">
        <h1>VPS Hosting</h1>
    </section>
    <section class="form-section">
        <h2>Triển Khai Máy Chủ Ngay</h2>
        <div class="tabs">
            <button>Tab 1</button>
            <button>Tab 2</button>
        </div>
        <form class="old-form">
            <input type="text" name="name" placeholder="Tên">
            <button type="submit">Gửi</button>
        </form>
    </section>
    <section class="pricing">
        <h2>Bảng giá</h2>
    </section>
</body>
</html>
HTML;

        $page = $this->service->import([
            'name' => 'VPS Landing Page',
            'slug' => 'vps-landing-page',
        ], $html, $this->user->id);

        // Old form section should be completely removed
        $this->assertStringNotContainsString('form-section', strtolower($page->html_body));
        $this->assertStringNotContainsString('Triển Khai Máy Chủ Ngay', $page->html_body);
        $this->assertStringNotContainsString('tabs', strtolower($page->html_body));
        $this->assertStringNotContainsString('<form', strtolower($page->html_body));
        
        // Hero and pricing should remain
        $this->assertStringContainsString('VPS Hosting', $page->html_body);
        $this->assertStringContainsString('B&#7843;ng gi', $page->html_body);
    }

    public function test_it_detects_theme_tokens_from_vps_html(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <style>
        :root {
            --primary: #7c3aed;
            --secondary: #4f46e5;
            --bg: #f5f3ff;
        }
        body { background: var(--bg); }
    </style>
</head>
<body>
    <section class="hero"><h1>VPS</h1></section>
</body>
</html>
HTML;

        $page = $this->service->import([
            'name' => 'VPS Landing Page',
            'slug' => 'vps-landing-page-2',
        ], $html, $this->user->id);

        $this->assertSame('#7c3aed', $page->theme_tokens['primary']);
        $this->assertSame('#4f46e5', $page->theme_tokens['primary_hover']);
        $this->assertSame('#f5f3ff', $page->theme_tokens['background']);
    }

    public function test_it_removes_form_placeholders(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<body>
    <section class="hero"><h1>Test</h1></section>
    {{form}}
    {{form_personal}}
    {{form_business}}
    {{forms_section}}
    <section class="footer">Footer</section>
</body>
</html>
HTML;

        $page = $this->service->import([
            'name' => 'Test Page',
            'slug' => 'test-page',
        ], $html, $this->user->id);

        $this->assertStringNotContainsString('{{form}}', $page->html_body);
        $this->assertStringNotContainsString('{{form_personal}}', $page->html_body);
        $this->assertStringNotContainsString('{{form_business}}', $page->html_body);
        $this->assertStringNotContainsString('{{forms_section}}', $page->html_body);
    }

    public function test_it_preserves_hero_and_content_sections(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<body>
    <header class="header">Header</header>
    <section class="hero">
        <h1>Amazing Product</h1>
        <p>Subheadline here</p>
    </section>
    <section class="features">
        <h2>Features</h2>
    </section>
    <section class="form-section">
        <form><input name="email"><button>Submit</button></form>
    </section>
    <footer>Footer</footer>
</body>
</html>
HTML;

        $page = $this->service->import([
            'name' => 'Test Page',
            'slug' => 'test-page-2',
        ], $html, $this->user->id);

        $this->assertStringContainsString('Amazing Product', $page->html_body);
        $this->assertStringContainsString('Subheadline here', $page->html_body);
        $this->assertStringContainsString('Features', $page->html_body);
        $this->assertStringContainsString('Header', $page->html_body);
        $this->assertStringContainsString('Footer', $page->html_body);
        
        // Form section should be gone
        $this->assertStringNotContainsString('form-section', strtolower($page->html_body));
    }
}