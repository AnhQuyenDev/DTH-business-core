<?php

namespace App\Events\Crm;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class LeadAssigned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $leadId,
        public readonly ?int $fromStaffId,
        public readonly int $toStaffId,
        public readonly ?int $assignedByUserId,
        public readonly string $reason,
        public readonly bool $forced,
        public readonly bool $companyOwnerTransferred,
    ) {}
}
