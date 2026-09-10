<?php

namespace Dth\Email\DTO;

final readonly class OutgoingEmail
{
    public function __construct(
        public int $sendingAccountId,
        public string $toEmail,
        public string $subject,
        public ?string $toName = null,
        public ?string $htmlBody = null,
        public ?string $textBody = null,
        public ?int $templateId = null,
        public ?string $relatedType = null,
        public ?int $relatedId = null,
        public ?string $idempotencyKey = null,
        public array $metadata = [],
    ) {}
}
