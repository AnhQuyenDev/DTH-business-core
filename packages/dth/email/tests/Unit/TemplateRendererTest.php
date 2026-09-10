<?php

namespace Dth\Email\Tests\Unit;

use Dth\Email\Services\TemplateRenderer;
use Dth\Email\Tests\TestCase;

class TemplateRendererTest extends TestCase
{
    public function test_it_renders_variables_and_escapes_html(): void
    {
        $renderer = new TemplateRenderer();

        $html = $renderer->render(
            'Hello {{ name }} - {{ company.name }}',
            ['name' => '<Admin>', 'company' => ['name' => 'DTH']],
        );

        $this->assertSame('Hello &lt;Admin&gt; - DTH', $html);
    }

    public function test_it_extracts_unique_sorted_variables_across_template_parts(): void
    {
        $renderer = new TemplateRenderer();

        $variables = $renderer->extractVariables(
            'Hello {{ name }}',
            '{{ company.name }} / {{name}}',
            '<a href="{{ unsubscribe_url }}">Unsubscribe</a>',
        );

        $this->assertSame([
            'company.name',
            'name',
            'unsubscribe_url',
        ], $variables);
    }

    public function test_it_reports_missing_nested_variables(): void
    {
        $renderer = new TemplateRenderer();

        $missing = $renderer->missingVariables(
            ['name', 'company.name', 'email'],
            ['name' => 'Alice', 'company' => ['name' => 'DTH']],
        );

        $this->assertSame(['email'], $missing);
    }
}
