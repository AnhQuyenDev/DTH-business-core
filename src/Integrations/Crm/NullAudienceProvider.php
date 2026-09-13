<?php

namespace Dth\Marketing\Integrations\Crm;

use Dth\Marketing\Contracts\AudienceProvider;
use Dth\Marketing\DTO\AudienceContactData;
use Dth\Marketing\DTO\AudienceContactReference;

final class NullAudienceProvider implements AudienceProvider
{
    public function available(): bool
    {
        return false;
    }

    public function capabilities(): array
    {
        return [];
    }

    public function upsertContact(AudienceContactData $contact): ?AudienceContactReference
    {
        return null;
    }

    public function addTags(string $contactReference, array $tags): void
    {
        // Deliberate no-op: callers must check capability before offering the action.
    }
}
