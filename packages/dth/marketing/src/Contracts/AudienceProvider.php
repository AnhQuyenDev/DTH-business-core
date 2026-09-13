<?php

namespace Dth\Marketing\Contracts;

use Dth\Marketing\DTO\AudienceContactData;
use Dth\Marketing\DTO\AudienceContactReference;

interface AudienceProvider extends IntegrationProvider
{
    public function upsertContact(AudienceContactData $contact): ?AudienceContactReference;

    /**
     * @param array<int, string> $tags
     */
    public function addTags(string $contactReference, array $tags): void;
}
