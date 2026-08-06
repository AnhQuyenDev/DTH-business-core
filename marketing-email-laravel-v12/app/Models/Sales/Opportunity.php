<?php

namespace App\Models\Sales;

use App\Enums\Sales\OpportunityStage;
use App\Models\Crm\Company;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Staff;
use App\Models\Marketing\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opportunity extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sales_opportunities';

    protected $fillable = [
        'opportunity_code',
        'lead_id',
        'company_id',
        'primary_contact_id',
        'assigned_staff_id',
        'price_book_id',
        'title',
        'service_interest',
        'stage',
        'estimated_value',
        'probability',
        'expected_close_date',
        'won_at',
        'lost_at',
        'lost_reason',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'stage' => OpportunityStage::class,
            'estimated_value' => 'decimal:2',
            'probability' => 'integer',
            'expected_close_date' => 'date',
            'won_at' => 'datetime',
            'lost_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function primaryContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'primary_contact_id');
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_staff_id');
    }

    public function priceBook(): BelongsTo
    {
        return $this->belongsTo(PriceBook::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'opportunity_contacts')
            ->withPivot(['role', 'is_primary'])
            ->withTimestamps();
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(OpportunityInteraction::class)
            ->orderByDesc('interaction_at')
            ->orderByDesc('id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function convertedCustomer(): HasOne
    {
        return $this->hasOne(
            Customer::class,
            'converted_from_opportunity_id'
        );
    }

    public function latestQuotation()
    {
        return $this->hasOne(Quotation::class)->latestOfMany();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isTerminal(): bool
    {
        return ($this->stage instanceof OpportunityStage
            ? $this->stage
            : OpportunityStage::from((string) $this->stage)
        )->isTerminal();
    }
}
