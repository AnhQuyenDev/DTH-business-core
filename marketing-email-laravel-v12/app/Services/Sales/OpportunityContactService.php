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

            if ($locked->company_id !== null) {
                $belongsToCompany = $locked
                    ->company
                    ?->contacts()
                    ->whereKey($contactId)
                    ->exists() ?? false;

                if (! $belongsToCompany) {
                    throw ValidationException::withMessages([
                        'contact_id' =>
                            'Liên hệ này không thuộc công ty của cơ hội kinh doanh.',
                    ]);
                }
            } elseif (
                $locked->primary_contact_id !== $contactId
                && ! $isPrimary
            ) {
                throw ValidationException::withMessages([
                    'contact_id' =>
                        'Liên hệ này không thuộc cơ hội kinh doanh cá nhân.',
                ]);
            }
            
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
