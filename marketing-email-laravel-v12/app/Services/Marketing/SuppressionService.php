<?php

namespace App\Services\Marketing;

use App\Models\Marketing\Campaign;
use App\Models\Marketing\Contact;
use App\Models\Marketing\SuppressionEntry;
use Illuminate\Support\Str;

class SuppressionService
{
    public function isSuppressed(string $email): bool
    {
        $normalized = strtolower(trim($email));

        return $normalized !== '' && SuppressionEntry::query()->whereRaw('lower(email) = ?', [$normalized])->exists();
    }

    public function suppress(
        string $email,
        string $reason,
        ?string $source = null,
        ?int $campaignId = null,
        ?int $contactId = null,
        ?string $note = null,
        ?int $createdBy = null
    ): SuppressionEntry {
        return SuppressionEntry::query()->updateOrCreate(
            [
                'email' => strtolower(trim($email)),
                'reason' => $reason,
            ],
            [
                'source' => $source ?? $reason,
                'campaign_id' => $campaignId,
                'contact_id' => $contactId,
                'note' => $note,
                'created_by' => $createdBy,
            ]
        );
    }
}