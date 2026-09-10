<?php

namespace Dth\Email\Services;

use Dth\Email\Models\EmailTemplate;

class TemplatePreviewDataFactory
{
    public function __construct(private readonly EmailTemplateService $templates) {}

    public function make(EmailTemplate $template): array
    {
        $data = [];

        foreach ($this->templates->variables($template) as $key) {
            data_set($data, $key, $this->sampleValue($key));
        }

        return $data;
    }

    private function sampleValue(string $key): string
    {
        $normalized = strtolower($key);

        return match (true) {
            str_contains($normalized, 'email') => 'customer@example.com',
            str_contains($normalized, 'phone'), str_contains($normalized, 'mobile') => '0901234567',
            str_contains($normalized, 'company') && str_ends_with($normalized, 'name') => 'Công ty ABC',
            str_ends_with($normalized, 'name') => 'Nguyễn Văn A',
            str_contains($normalized, 'amount'), str_contains($normalized, 'total'), str_contains($normalized, 'price') => '10.000.000 VNĐ',
            str_contains($normalized, 'date') => now()->format('d/m/Y'),
            str_contains($normalized, 'url'), str_contains($normalized, 'link') => 'https://example.com',
            str_contains($normalized, 'number'), str_contains($normalized, 'code') => 'DTH-001',
            default => 'Dữ liệu mẫu',
        };
    }
}
