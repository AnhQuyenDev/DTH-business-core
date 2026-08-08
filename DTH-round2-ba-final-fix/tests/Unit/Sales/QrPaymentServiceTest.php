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
        $url = $this->service->generateUrl(
            'BIDV',
            '0987654321',
            500000,
            'QT202600001 ABC'
        );

        $this->assertStringContainsString('amount=500000', $url);
        $this->assertStringContainsString(urlencode('QT202600001 ABC'), $url);
    }

    public function test_generates_html(): void
    {
        $html = $this->service->generateHtml(
            'VCB',
            '1234567890',
            null,
            null,
            null,
            150
        );

        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('width="150"', $html);
    }

    public function test_builds_stable_transfer_content_from_quotation_code_only(): void
    {
        $content = $this->service->buildTransferContent(
            'QT-2026-000001',
            'CONG TY TNHH ABC'
        );

        $this->assertSame('QT2026000001', $content);
        $this->assertStringNotContainsString('CONG TY', $content);
    }

    public function test_transfer_content_is_sanitized_and_short(): void
    {
        $content = $this->service->buildTransferContent(
            'QT-2026/000001@DTH',
            'Tên khách không được đưa vào nội dung chuyển khoản'
        );

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9 ]+$/', $content);
        $this->assertLessThanOrEqual(25, mb_strlen($content));
    }

    public function test_empty_account_name_not_in_url(): void
    {
        $url = $this->service->generateUrl(
            'VCB',
            '1234567890',
            100000,
            'Test'
        );

        $this->assertStringNotContainsString('accountName', $url);
    }
}
