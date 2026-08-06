<?php

namespace App\Models\Crm;

use App\Enums\Crm\CompanyLifecycleStage;
use App\Models\Marketing\Contact;
use App\Models\Marketing\LandingPageSubmission;
use App\Models\Sales\Opportunity;
use App\Services\Crm\CompanyNormalizationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_code',
        'legal_name',
        'normalized_name',
        'tax_code',
        'email_domain',
        'website',
        'phone',
        'normalized_phone',
        'industry',
        'address',
        'province',
        'country_code',
        'lifecycle_stage',
        'account_owner_staff_id',
        'created_from_submission_id',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'lifecycle_stage' => CompanyLifecycleStage::class,
            'metadata' => 'array',
        ];
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function businessProfiles(): HasMany
    {
        return $this->hasMany(BusinessContactProfile::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Company $company): void {
            $normalizer = app(CompanyNormalizationService::class);

            $company->normalized_name = $normalizer->normalizeName(
                $company->legal_name
            ) ?? '';

            $company->tax_code = $normalizer->normalizeTaxCode(
                $company->tax_code
            );

            $company->email_domain = $normalizer->normalizeDomain(
                $company->email_domain
            );

            $company->normalized_phone = $normalizer->normalizePhone(
                $company->phone ?? $company->normalized_phone
            );
        });
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'company_contacts')
            ->withPivot([
                'job_title',
                'department',
                'decision_role',
                'is_primary',
                'is_active',
                'joined_at',
                'left_at',
            ])
            ->withTimestamps();
    }

    public function accountOwner(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'account_owner_staff_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CompanyAssignment::class);
    }

    public function matchCandidates(): HasMany
    {
        return $this->hasMany(CompanyMatchCandidate::class, 'suggested_company_id');
    }

    public function createdFromSubmission(): BelongsTo
    {
        return $this->belongsTo(LandingPageSubmission::class, 'created_from_submission_id');
    }
}
