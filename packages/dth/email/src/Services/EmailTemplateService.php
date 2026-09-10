<?php

namespace Dth\Email\Services;

use Dth\Email\DTO\RenderedEmailTemplate;
use Dth\Email\Enums\EmailTemplateStatus;
use Dth\Email\Models\EmailTemplate;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

class EmailTemplateService
{
    public function __construct(private readonly TemplateRenderer $renderer) {}

    public function create(array $attributes): EmailTemplate
    {
        $attributes['template_key'] = $this->normalizeKey(
            $attributes['template_key'] ?? $attributes['name'] ?? 'template',
        );

        return EmailTemplate::query()->create($attributes);
    }

    public function update(EmailTemplate $template, array $attributes): EmailTemplate
    {
        if (array_key_exists('template_key', $attributes)) {
            $attributes['template_key'] = $this->normalizeKey((string) $attributes['template_key']);
        }

        $template->update($attributes);

        return $template->refresh();
    }

    public function findActiveByKey(string $templateKey): EmailTemplate
    {
        $key = $this->normalizeKey($templateKey);

        return EmailTemplate::query()
            ->where('template_key', $key)
            ->where('status', EmailTemplateStatus::Active->value)
            ->firstOrFail();
    }

    public function renderByKey(string $templateKey, array $variables, bool $strict = true): RenderedEmailTemplate
    {
        return $this->render($this->findActiveByKey($templateKey), $variables, $strict);
    }

    public function render(EmailTemplate $template, array $variables, bool $strict = false): RenderedEmailTemplate
    {
        $required = $this->variables($template);
        $missing = $this->renderer->missingVariables($required, $variables);

        if ($strict && $missing !== []) {
            throw new InvalidArgumentException(
                'Missing email template variables: '.implode(', ', $missing),
            );
        }

        return new RenderedEmailTemplate(
            templateId: $template->id,
            templateKey: $template->template_key,
            subject: $this->renderer->renderSubject($template->subject, $variables),
            preheader: $this->renderer->renderPreheader($template->preheader, $variables),
            htmlBody: $this->renderer->render($template->html_body, $variables),
            textBody: $this->renderer->renderText($template->text_body, $variables),
            variables: $required,
            missingVariables: $missing,
        );
    }

    public function renderForDelivery(EmailTemplate $template, array $variables): RenderedEmailTemplate
    {
        if ($template->status !== EmailTemplateStatus::Active) {
            throw new LogicException('Only active email templates can be used for delivery.');
        }

        return $this->render($template, $variables, true);
    }

    /**
     * @return list<string>
     */
    public function variables(EmailTemplate $template): array
    {
        return $this->renderer->extractVariables(
            $template->subject,
            $template->preheader,
            $template->html_body,
            $template->text_body,
        );
    }

    public function normalizeKey(string $value): string
    {
        $key = Str::of($value)
            ->lower()
            ->trim()
            ->replace(['/', '\\', ' '], '.')
            ->replaceMatches('/[^a-z0-9._-]+/', '-')
            ->replaceMatches('/[.]{2,}/', '.')
            ->trim('.-_')
            ->toString();

        if ($key === '') {
            throw new InvalidArgumentException('Template key cannot be empty.');
        }

        return $key;
    }
}
