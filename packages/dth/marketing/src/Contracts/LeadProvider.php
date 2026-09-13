<?php

namespace Dth\Marketing\Contracts;

use Dth\Marketing\DTO\LeadIntakeData;
use Dth\Marketing\DTO\LeadReference;

interface LeadProvider extends IntegrationProvider
{
    public function createOrUpdateFromMarketing(LeadIntakeData $intake): ?LeadReference;
}
