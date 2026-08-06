<?php

namespace App\Services\Sales;

use App\Models\Sales\Opportunity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OpportunityContactService
{
    public function upsert(
        Opportunity $opportunity,
        int $contactId,
        string $role = 'other',
        bool $isPrimary = false,
        ?int $actorUserId = null,
    ): Opportunity {
        return DB::transaction(function () use (
            $opportunity,
            $contactId,
            $role,
            $isPrimary,
            $actorUserId,
        ): Opportunity {
            $locked = Opportunity::query()
                ->whereKey($opportunity->id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Không cho hạ Primary Contact hiện tại xuống thường
             * nếu chưa chọn một Primary Contact thay thế.
             */
            if (
                ! $isPrimary
                && $locked->primary_contact_id === $contactId
            ) {
                throw ValidationException::withMessages([
                    'is_primary' => __(
                        'validation.opportunity_primary_contact_cannot_be_demoted'
                    ),
                ]);
            }

            if ($isPrimary) {
                $locked->contacts()
                    ->newPivotStatement()
                    ->where('opportunity_id', $locked->id)
                    ->update([
                        'is_primary' => false,
                    ]);
            }

            $locked->contacts()->syncWithoutDetaching([
                $contactId => [
                    'role' => $isPrimary
                        ? 'primary_contact'
                        : $role,
                    'is_primary' => $isPrimary,
                ],
            ]);

            $updateData = [
                'updated_by' => $actorUserId,
            ];

            if ($isPrimary) {
                $updateData['primary_contact_id'] = $contactId;
            }

            $locked->update($updateData);

            return $locked->fresh([
                'primaryContact',
                'contacts',
            ]);
        });
    }

    public function detach(
        Opportunity $opportunity,
        int $contactId,
        ?int $actorUserId = null,
    ): Opportunity {
        return DB::transaction(function () use (
            $opportunity,
            $contactId,
            $actorUserId,
        ): Opportunity {
            $locked = Opportunity::query()
                ->whereKey($opportunity->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->primary_contact_id === $contactId) {
                throw ValidationException::withMessages([
                    'contact_id' => __(
                        'validation.opportunity_primary_contact_cannot_be_detached'
                    ),
                ]);
            }

            $locked->contacts()->detach($contactId);

            $locked->update([
                'updated_by' => $actorUserId,
            ]);

            return $locked->fresh([
                'primaryContact',
                'contacts',
            ]);
        });
    }
}
