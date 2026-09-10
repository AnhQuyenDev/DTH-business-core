<?php

namespace Dth\Email\DTO;

final readonly class RenderedEmailTemplate
{
    /**
     * @param list<string> $variables
     * @param list<string> $missingVariables
     */
    public function __construct(
        public int $templateId,
        public ?string $templateKey,
        public string $subject,
        public ?string $preheader,
        public string $htmlBody,
        public ?string $textBody,
        public array $variables,
        public array $missingVariables,
    ) {}
}
