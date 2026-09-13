<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Enums\FormAudienceType;
use Dth\Marketing\Enums\FormTemplateStatus;
use Dth\Marketing\Models\FormTemplate;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class FormTemplateHtmlImportService
{
    public function __construct(
        private readonly HtmlFormParser $parser,
    ) {}

    public function fromPath(array $data, string $path, ?int $userId = null): FormTemplate
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('The HTML form file cannot be read.');
        }

        $size = filesize($path);
        if ($size === false || $size > 2 * 1024 * 1024) {
            throw new RuntimeException('Form Template HTML must be 2 MB or smaller.');
        }

        $html = file_get_contents($path);
        if ($html === false || trim($html) === '') {
            throw new RuntimeException('The HTML form file is empty.');
        }

        return $this->import($data, $html, $userId);
    }

    public function import(array $data, string $sourceHtml, ?int $userId = null): FormTemplate
    {
        $audienceType = FormAudienceType::tryFrom((string) ($data['audience_type'] ?? ''));
        if ($audienceType === null) {
            throw new RuntimeException('Form Template audience type must be Personal or Business.');
        }

        $prepared = $this->parser->prepareForm($sourceHtml, $audienceType);
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('Form Template name is required.');
        }

        return DB::transaction(function () use ($data, $prepared, $audienceType, $name, $userId): FormTemplate {
            $template = FormTemplate::query()->create([
                'name' => $name,
                'slug' => $data['slug'] ?? null,
                'description' => $data['description'] ?? null,
                'audience_type' => $audienceType->value,
                'status' => FormTemplateStatus::Draft->value,
                'submit_button_text' => $prepared['submit_button_text']
                    ?: (string) ($data['submit_button_text'] ?? 'Submit'),
                'success_message' => $data['success_message'] ?? null,
                'redirect_url' => $data['redirect_url'] ?? null,
                'html_body' => $prepared['html_body'],
                'schema' => $prepared['schema'],
                'created_by' => $userId,
            ]);

            $template->fields()->createMany($prepared['fields']);

            return $template->fresh('fields');
        });
    }

    public function detectAudienceType(string $formHtml): FormAudienceType
    {
        return $this->parser->detectAudienceType($formHtml);
    }
}
