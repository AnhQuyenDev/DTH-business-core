<?php

namespace Dth\Crm\Models;

use Dth\Crm\Support\CodeGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;

    protected $table = 'crm_leads';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'estimated_value' => 'decimal:2',
            'assigned_at' => 'datetime',
            'converted_to_opportunity_at' => 'datetime',
            'attribution' => 'array',
            'form_answers' => 'array',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (blank($model->lead_code)) {
                $model->lead_code = app(CodeGenerator::class)->make('LD');
            }
        });
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** CRM assignment profile. The employee master is available through assignedAgentProfile.employee. */
    public function assignedAgentProfile(): BelongsTo
    {
        return $this->belongsTo(CrmAgentProfile::class, 'assigned_agent_profile_id');
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->assignedAgentProfile();
    }

    public function qualification(): HasOne
    {
        return $this->hasOne(ContactQualification::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->orderByDesc('activity_at');
    }

    public function distributionEvents(): HasMany
    {
        return $this->hasMany(LeadDistributionEvent::class);
    }
}
