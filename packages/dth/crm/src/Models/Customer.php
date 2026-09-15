<?php

namespace Dth\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $table = 'crm_customers';
    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (self $customer): void {
            if (blank($customer->customer_code)) {
                $customer->customer_code = app(\Dth\Crm\Support\CodeGenerator::class)->make('CUS');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'converted_at' => 'datetime',
            'first_purchase_at' => 'datetime',
            'latest_purchase_at' => 'datetime',
            'total_revenue' => 'decimal:2',
            'next_follow_up_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function originLead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'origin_lead_id');
    }

    public function convertedByAgentProfile(): BelongsTo
    {
        return $this->belongsTo(CrmAgentProfile::class, 'converted_by_agent_profile_id');
    }

    public function convertedByAgent(): BelongsTo
    {
        return $this->convertedByAgentProfile();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CustomerAssignment::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(CustomerInteraction::class)->orderByDesc('interaction_at');
    }
}
