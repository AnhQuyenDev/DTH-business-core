<?php

namespace Dth\Marketing\Integrations\Crm;

use Dth\Marketing\Contracts\LeadProvider;
use Dth\Marketing\DTO\LeadIntakeData;
use Dth\Marketing\DTO\LeadReference;

final class NullLeadProvider implements LeadProvider
{
    public function available(): bool
    {
        return false;
    }

    public function capabilities(): array
    {
        return [];
    }

    public function createOrUpdateFromMarketing(LeadIntakeData $intake): ?LeadReference
    {
        return null;
    }
}
