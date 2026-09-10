<?php

namespace Dth\Email\Services;

use Dth\Email\Contracts\DnsResolver;
use Dth\Email\DTO\DomainVerificationResult;
use Dth\Email\Enums\SendingDomainStatus;
use Dth\Email\Models\SendingDomain;

class DomainVerificationService
{
    public function __construct(private readonly DnsResolver $dns) {}

    public function verify(SendingDomain $domain): DomainVerificationResult
    {
        $name = mb_strtolower(trim($domain->domain));

        $spfStatus = $this->hasRecordStartingWith($this->dns->txt($name), 'v=spf1')
            ? 'verified'
            : 'failed';

        $dmarcStatus = $this->hasRecordStartingWith($this->dns->txt('_dmarc.'.$name), 'v=dmarc1')
            ? 'verified'
            : 'failed';

        $selector = trim((string) $domain->dkim_selector);

        if ($selector === '') {
            $dkimStatus = 'pending';
        } else {
            $dkimRecords = $this->dns->txt($selector.'._domainkey.'.$name);
            $dkimStatus = $this->hasDkimRecord($dkimRecords) ? 'verified' : 'failed';
        }

        $allVerified = $spfStatus === 'verified'
            && $dkimStatus === 'verified'
            && $dmarcStatus === 'verified';

        $domainStatus = $allVerified
            ? SendingDomainStatus::Verified
            : ($dkimStatus === 'pending' ? SendingDomainStatus::Pending : SendingDomainStatus::Failed);

        $domain->forceFill([
            'spf_status' => $spfStatus,
            'dkim_status' => $dkimStatus,
            'dmarc_status' => $dmarcStatus,
            'status' => $domainStatus,
            'last_checked_at' => now(),
            'verified_at' => $allVerified ? ($domain->verified_at ?? now()) : null,
        ])->save();

        return new DomainVerificationResult(
            spfStatus: $spfStatus,
            dkimStatus: $dkimStatus,
            dmarcStatus: $dmarcStatus,
            domainStatus: $domainStatus,
        );
    }

    /** @param list<string> $records */
    private function hasRecordStartingWith(array $records, string $prefix): bool
    {
        $prefix = mb_strtolower($prefix);

        foreach ($records as $record) {
            if (str_starts_with(mb_strtolower(trim($record)), $prefix)) {
                return true;
            }
        }

        return false;
    }

    /** @param list<string> $records */
    private function hasDkimRecord(array $records): bool
    {
        foreach ($records as $record) {
            $normalized = mb_strtolower(trim($record));

            if (str_contains($normalized, 'p=') && (str_starts_with($normalized, 'v=dkim1') || str_contains($normalized, 'k='))) {
                return true;
            }
        }

        return false;
    }
}
