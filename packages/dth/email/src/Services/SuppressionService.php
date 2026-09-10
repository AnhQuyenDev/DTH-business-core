<?php

namespace Dth\Email\Services;

use Dth\Email\Enums\SuppressionReason;
use Dth\Email\Models\EmailMessage;
use Dth\Email\Models\EmailSuppression;
use LogicException;

class SuppressionService
{
    public function isSuppressed(string $email): bool
    {
        return EmailSuppression::query()
            ->where('email', $this->normalize($email))
            ->whereNull('released_at')
            ->exists();
    }

    public function suppress(
        string $email,
        SuppressionReason $reason,
        ?string $source = null,
        ?EmailMessage $message = null,
        ?string $note = null,
        ?int $createdBy = null,
    ): EmailSuppression {
        $normalized = $this->normalize($email);

        $active = EmailSuppression::query()
            ->where('email', $normalized)
            ->whereNull('released_at')
            ->latest('id')
            ->first();

        $attributes = [
            'reason' => $reason,
            'source' => $source,
            'message_id' => $message?->id,
            'note' => $note,
            'created_by' => $createdBy,
        ];

        if ($active) {
            $active->update($attributes);

            return $active->refresh();
        }

        return EmailSuppression::query()->create([
            'email' => $normalized,
            ...$attributes,
        ]);
    }

    public function canRelease(EmailSuppression $suppression): bool
    {
        if ($suppression->released_at !== null) {
            return false;
        }

        return in_array(
            $suppression->reason,
            [
                SuppressionReason::Manual,
                SuppressionReason::Unsubscribe,
            ],
            true,
        );
    }

    public function release(
        EmailSuppression $suppression,
        ?int $releasedBy = null,
        ?string $source = null,
        ?string $note = null,
    ): EmailSuppression {
        if (! $this->canRelease($suppression)) {
            throw new LogicException(
                'Bounce and complaint suppressions cannot be released manually, and an already released suppression cannot be released again.'
            );
        }

        $suppression->update([
            'released_at' => now(),
            'released_by' => $releasedBy,
            'release_source' => $source,
            'release_note' => $note,
        ]);

        return $suppression->refresh();
    }

    private function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
