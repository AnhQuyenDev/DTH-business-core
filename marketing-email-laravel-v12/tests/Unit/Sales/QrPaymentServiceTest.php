<?php

namespace Tests\Unit\Sales;

use App\Services\Sales\QrPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private QrPaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QrPaymentService;
    }

    public function test_generates_vietqr_url(): void
    {
        $url = $this->service->generateUrl('VCB', '1234567890');

        $this->assertStringContainsString('vietqr.io', $url);
        $this->assertStringContainsString('VCB-1234567890', $url);
    }

    public function test_generates_url_with_amount_and_content(): void
    {
        $url = $this->service->generateUrl('BIDV', '0987654321', 500000, 'QT202600001 ABC');

        $this->assertStringContainsString('amount=500000', $url);
        $this->assertStringContainsString(urlencode('QT202600001 ABC'), $url);
    }

    public function test_generates_html(): void
    {
        $html = $this->service->generateHtml('VCB', '1234567890', null, null, null, 150);

        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('width="150"', $html);
    }

    public function test_builds_transfer_content(): void
    {
        $content = $this->service->buildTransferContent('QT202600001', 'CONG TY TNHH ABC');

        $this->assertStringContainsString('QT202600001', $content);
        $this->assertStringContainsString('CONG TY TNHH ABC', $content);
    }

    public function test_sanitizes_special_characters(): void
    {
        $content = $this->service->buildTransferContent('QT202600001', 'Công ty TNHH XYZ (Đã đăng ký)');

        $this->assertStringNotContainsString('(', $content);
        $this->assertStringNotContainsString('Đ', $content);
    }

    public function test_truncates_long_content(): void
    {
        $longName = str_repeat('A', 100);
        $content = $this->service->buildTransferContent('QT202600001', $longName);

        $this->assertLessThanOrEqual(60, strlen($content));
    }

    public function test_empty_account_name_not_in_url(): void
    {
        $url = $this->service->generateUrl('VCB', '1234567890', 100000, 'Test');

        $this->assertStringNotContainsString('accountName', $url);
    }
}
