<?php

namespace Dth\Crm\Models;

use Dth\Crm\Support\CodeGenerator;
use Dth\Crm\Support\Normalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $table = 'crm_companies';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            $normalizer = app(Normalizer::class);
            if (blank($model->company_code)) {
                $model->company_code = app(CodeGenerator::class)->make('COM');
            }
            $model->normalized_name = $normalizer->companyName($model->legal_name) ?? '';
            $model->tax_code = $normalizer->taxCode($model->tax_code);
            $model->normalized_phone = $normalizer->phone($model->phone);
            if (! $model->email_domain && is_array($model->metadata)) {
                $model->email_domain = $normalizer->domain($model->metadata['business_email'] ?? null);
            }
        });
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'crm_company_contacts', 'company_id', 'contact_id')
            ->withPivot(['job_title', 'department', 'decision_role', 'is_primary', 'is_active'])
            ->withTimestamps();
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /** CRM assignment profile. Employee identity is available via accountOwner.employee. */
    public function accountOwner(): BelongsTo
    {
        return $this->belongsTo(CrmAgentProfile::class, 'account_owner_agent_profile_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CompanyAssignment::class);
    }
}
