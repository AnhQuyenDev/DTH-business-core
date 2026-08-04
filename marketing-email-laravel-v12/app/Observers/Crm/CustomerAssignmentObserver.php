<?php

namespace App\Observers\Crm;

use App\Enums\Crm\CustomerAssignmentStatus;
use App\Models\Crm\CustomerAssignment;
use Illuminate\Support\Facades\Auth;

class CustomerAssignmentObserver
{
    public function creating(CustomerAssignment $assignment): void
    {
        $this->closeExistingActives($assignment);
    }

    public function updating(CustomerAssignment $assignment): void
    {
        if ($assignment->isDirty('status') && $assignment->status === CustomerAssignmentStatus::Active) {
            $this->closeExistingActives($assignment, $assignment->id);
        }
    }

    private function closeExistingActives(CustomerAssignment $assignment, ?int $ignoreId = null): void
    {
        if (! $assignment->customer_id) {
            return;
        }

        $query = CustomerAssignment::query()
            ->where('customer_id', $assignment->customer_id)
            ->where('status', CustomerAssignmentStatus::Active->value);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        $now = now();

        $query->update([
            'status' => CustomerAssignmentStatus::Ended->value,
            'ends_at' => $now,
            'ended_at' => $now,
            'ended_by_user_id' => Auth::id(),
            'note' => 'Chuyển giao cho nhân viên khác',
        ]);
    }
}
