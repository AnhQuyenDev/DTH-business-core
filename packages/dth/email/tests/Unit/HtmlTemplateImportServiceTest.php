<?php

namespace Dth\Email\Tests\Unit;

use Dth\Email\Services\HtmlTemplateImportService;
use Dth\Email\Tests\TestCase;
use InvalidArgumentException;

class HtmlTemplateImportServiceTest extends TestCase
{
    public function test_it_reads_an_html_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'dth-email-');
        file_put_contents($path, "\xEF\xBB\xBF<html><body>Hello</body></html>");

        try {
            $html = (new HtmlTemplateImportService())->fromPath($path);
            $this->assertSame('<html><body>Hello</body></html>', $html);
        } finally {
            @unlink($path);
        }
    }

    public function test_it_rejects_an_empty_html_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'dth-email-');
        file_put_contents($path, '   ');

        try {
            $this->expectException(InvalidArgumentException::class);
            (new HtmlTemplateImportService())->fromPath($path);
        } finally {
            @unlink($path);
        }
    }
}
