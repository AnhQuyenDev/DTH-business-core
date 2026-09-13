<?php

namespace Dth\Marketing\Models;

use Dth\Marketing\Support\UiText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ContactListMember extends Model
{
    protected $table = 'marketing_contact_list_members';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $member): void {
            $email = self::normalizeEmail($member->normalized_email);
            $phone = self::normalizePhone($member->normalized_phone);
            $contactReference = trim((string) $member->contact_reference);

            $member->normalized_email = $email;
            $member->normalized_phone = $phone;
            $member->contact_reference = $contactReference !== '' ? $contactReference : null;

            $manualIdentityChanged = $member->source_submission_id === null && (
                blank($member->member_key)
                || $member->isDirty('audience_type')
                || $member->isDirty('normalized_email')
                || $member->isDirty('normalized_phone')
                || $member->isDirty('contact_reference')
            );

            if (blank($member->member_key) || $manualIdentityChanged) {
                $audienceType = trim((string) $member->audience_type);
                if (! in_array($audienceType, ['personal', 'business'], true)) {
                    throw ValidationException::withMessages([
                        'audience_type' => UiText::get(
                            'audience.validation.type',
                            'Customer type must be Personal or Business.',
                        ),
                    ]);
                }

                $identity = $email !== null
                    ? 'email:'.$email
                    : ($phone !== null
                        ? 'phone:'.$phone
                        : (filled($member->contact_reference) ? 'contact:'.Str::lower((string) $member->contact_reference) : null));

                if ($identity === null) {
                    throw ValidationException::withMessages([
                        'normalized_email' => UiText::get(
                            'audience.validation.identity',
                            'Provide an email, phone number, or external contact reference.',
                        ),
                    ]);
                }

                $member->member_key = hash('sha256', $audienceType.'|'.$identity);
            }

            if ((string) $member->status === 'unsubscribed') {
                $member->unsubscribed_at ??= now();
            } else {
                $member->status = 'subscribed';
                $member->subscribed_at ??= now();
                $member->unsubscribed_at = null;
            }
        });
    }

    public function contactList(): BelongsTo
    {
        return $this->belongsTo(ContactList::class, 'contact_list_id');
    }

    public function sourceSubmission(): BelongsTo
    {
        return $this->belongsTo(LandingPageSubmission::class, 'source_submission_id');
    }

    private static function normalizeEmail(mixed $email): ?string
    {
        $email = Str::lower(trim((string) $email));

        return $email !== '' ? $email : null;
    }

    private static function normalizePhone(mixed $phone): ?string
    {
        $phone = preg_replace('/[^0-9+]/', '', trim((string) $phone)) ?: '';

        return $phone !== '' ? mb_substr($phone, 0, 50) : null;
    }
}
