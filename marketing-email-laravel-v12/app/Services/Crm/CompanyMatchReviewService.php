<?php

namespace App\Services\Crm;

use App\Models\Crm\CompanyMatchCandidate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CompanyMatchReviewService
{
    public function __construct(
        private readonly CompanyContactLinkService $linkService,
    ) {}

    public function accept(
        CompanyMatchCandidate $candidate,
        int $reviewedByUserId,
    ): CompanyMatchCandidate {
        return DB::transaction(function () use (
            $candidate,
            $reviewedByUserId,
        ): CompanyMatchCandidate {
            $candidate = CompanyMatchCandidate::query()
                ->with([
                    'contact.businessProfile',
                    'submission',
                    'suggestedCompany',
                ])
                ->whereKey($candidate->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($candidate->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Candidate này đã được xử lý.',
                ]);
            }

            if (
                $candidate->contact === null
                || $candidate->suggestedCompany === null
            ) {
                throw ValidationException::withMessages([
                    'candidate' => 'Candidate thiếu Contact hoặc Company.',
                ]);
            }

            $profile = $candidate->contact->businessProfile;

            $this->linkService->link(
                company: $candidate->suggestedCompany,
                contact: $candidate->contact,
                profile: $profile,
                submission: $candidate->submission,
                jobTitle: $profile?->contact_position,
            );

            $candidate->update([
                'status' => 'accepted',
                'reviewed_by_user_id' => $reviewedByUserId,
                'reviewed_at' => now(),
            ]);

            $candidate->submission->lead?->update([
                'company_id' => $candidate->suggestedCompany->id,
            ]);

            return $candidate->fresh();
        });
    }

    public function reject(
        CompanyMatchCandidate $candidate,
        int $reviewedByUserId,
    ): CompanyMatchCandidate {
        return DB::transaction(function () use (
            $candidate,
            $reviewedByUserId,
        ): CompanyMatchCandidate {
            $candidate = CompanyMatchCandidate::query()
                ->whereKey($candidate->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($candidate->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => 'Candidate này đã được xử lý.',
                ]);
            }

            $candidate->update([
                'status' => 'rejected',
                'reviewed_by_user_id' => $reviewedByUserId,
                'reviewed_at' => now(),
            ]);

            return $candidate->fresh();
        });
    }
}
