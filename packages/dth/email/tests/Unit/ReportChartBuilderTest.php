<?php

namespace Dth\Email\Tests\Unit;

use Dth\Email\Support\ReportChartBuilder;
use PHPUnit\Framework\TestCase;

class ReportChartBuilderTest extends TestCase
{
    public function test_line_chart_is_emitted_as_embeddable_svg_data_uri(): void
    {
        $uri = (new ReportChartBuilder())->lineChartDataUri(
            labels: ['01/09', '02/09', '03/09'],
            series: [
                [
                    'label' => 'Sent',
                    'values' => [10, 20, 15],
                    'color' => '#f59e0b',
                ],
            ],
        );

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $uri);

        $svg = base64_decode(substr($uri, strlen('data:image/svg+xml;base64,')), true);

        $this->assertIsString($svg);
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('Sent', $svg);
        $this->assertStringContainsString('polyline', $svg);
    }
}
