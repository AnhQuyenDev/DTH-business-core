<?php

namespace App\Events\Crm;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ContactQualificationTransitioned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $qualificationId,
        public readonly int $leadId,
        public readonly string $fromStatus,
        public readonly string $toStatus,
        public readonly ?int $actorUserId,
        public readonly array $changedValues = [],
    ) {}
}
