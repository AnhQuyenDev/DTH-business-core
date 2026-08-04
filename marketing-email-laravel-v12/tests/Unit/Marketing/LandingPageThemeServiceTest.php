<?php

namespace Tests\Unit\Marketing;

use App\Services\Marketing\LandingPageThemeService;
use Tests\TestCase;

class LandingPageThemeServiceTest extends TestCase
{
    private LandingPageThemeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LandingPageThemeService();
    }

    public function test_it_detects_css_variables_from_vps_html(): void
    {
        $html = <<<'HTML'
<style>
:root {
    --primary: #7c3aed;
    --secondary: #4f46e5;
    --bg: #f5f3ff;
}
body { background: var(--bg); }
</style>
HTML;

        $tokens = $this->service->detectFromHtml($html);

        $this->assertSame('#7c3aed', $tokens['primary']);
        $this->assertSame('#4f46e5', $tokens['primary_hover']);
        $this->assertSame('#f5f3ff', $tokens['background']);
    }

    public function test_it_detects_lp_prefixed_variables(): void
    {
        $html = <<<'HTML'
<style>
:root {
    --lp-primary: #6366f1;
    --lp-background: #f8fafc;
    --lp-surface: #ffffff;
    --lp-text: #0f172a;
}
</style>
HTML;

        $tokens = $this->service->detectFromHtml($html);

        $this->assertSame('#6366f1', $tokens['primary']);
        $this->assertSame('#f8fafc', $tokens['background']);
        $this->assertSame('#ffffff', $tokens['surface']);
        $this->assertSame('#0f172a', $tokens['text']);
    }

    public function test_it_falls_back_to_defaults_when_no_variables(): void
    {
        $html = '<html><body>No CSS variables here</body></html>';

        $tokens = $this->service->detectFromHtml($html);

        $this->assertSame('#2563eb', $tokens['primary']);
        $this->assertSame('#f8fafc', $tokens['background']);
    }

    public function test_it_normalizes_short_hex_colors(): void
    {
        $html = <<<'HTML'
<style>
:root {
    --primary: #abc;
}
</style>
HTML;

        $tokens = $this->service->detectFromHtml($html);

        $this->assertSame('#aabbcc', $tokens['primary']);
    }
}