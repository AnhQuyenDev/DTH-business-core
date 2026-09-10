<?php

namespace Dth\Email\DTO;

use Dth\Email\Enums\SendingDomainStatus;

final readonly class DomainVerificationResult
{
    public function __construct(
        public string $spfStatus,
        public string $dkimStatus,
        public string $dmarcStatus,
        public SendingDomainStatus $domainStatus,
    ) {}

    public function isVerified(): bool
    {
        return $this->domainStatus === SendingDomainStatus::Verified;
    }
}
