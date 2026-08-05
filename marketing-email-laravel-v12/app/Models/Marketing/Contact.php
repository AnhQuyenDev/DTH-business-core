<?php

namespace App\Models\Marketing;

use App\Enums\Crm\ContactType;
use App\Models\Crm\BusinessContactProfile;
use App\Models\Crm\Company;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\Customer;
use App\Models\Crm\PersonalContactProfile;
use App\Models\User;
use App\Services\Marketing\AuditLogService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contact_type',
        'owner_user_id',
    ];

    protected function casts(): array
    {
        return [
            'contact_type' => ContactType::class,
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $contact): void {
            app(AuditLogService::class)->log('contact.created', $contact, [], $contact->attributesToArray());
        });

        static::updated(function (self $contact): void {
            app(AuditLogService::class)->log('contact.updated', $contact, $contact->getOriginal(), $contact->getChanges());
        });

        static::deleted(function (self $contact): void {
            app(AuditLogService::class)->log('contact.deleted', $contact, $contact->getOriginal(), []);
        });

        static::deleting(function (self $contact): void {
            $contact->personalProfile?->delete();
            $contact->businessProfile?->delete();
            $contact->qualification?->delete();
            $contact->customFieldValues()->each(fn ($v) => $v->delete());
            $contact->landingPageSubmissions()->each(fn ($s) => $s->delete());
        });
    }

    public function landingPageSubmissions(): HasMany
    {
        return $this->hasMany(LandingPageSubmission::class);
    }

    // Accessors for backward compatibility - delegate to the appropriate profile

    public function getFirstNameAttribute(): ?string
    {
        return $this->personalProfile?->first_name;
    }

    public function getLastNameAttribute(): ?string
    {
        return $this->personalProfile?->last_name;
    }

    public function getFullNameAttribute(): ?string
    {
        if ($this->personalProfile) {
            return trim(implode(' ', array_filter([
                $this->personalProfile->first_name,
                $this->personalProfile->last_name,
            ]))) ?: null;
        }

        return $this->businessProfile?->legal_representative;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->personalProfile?->email
            ?? $this->businessProfile?->business_email;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->personalProfile?->phone
            ?? $this->businessProfile?->business_phone;
    }

    public function getCompanyNameAttribute(): ?string
    {
        return $this->businessProfile?->company_name;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function customFieldValues(): HasMany
    {
        return $this->hasMany(ContactCustomFieldValue::class);
    }

    public function campaignRecipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function suppressionEntries(): HasMany
    {
        return $this->hasMany(SuppressionEntry::class);
    }

    public function personalProfile(): HasOne
    {
        return $this->hasOne(PersonalContactProfile::class);
    }

    public function businessProfile(): HasOne
    {
        return $this->hasOne(BusinessContactProfile::class);
    }

    public function qualification(): HasOne
    {
        return $this->hasOne(ContactQualification::class);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_contacts')
            ->withPivot([
                'job_title',
                'department',
                'decision_role',
                'is_primary',
                'is_active',
            ])
            ->withTimestamps();
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }
}
