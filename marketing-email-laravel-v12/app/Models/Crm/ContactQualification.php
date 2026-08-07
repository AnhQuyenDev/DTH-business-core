<?php

namespace App\Models\Crm;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\QualificationResult;
use App\Models\Marketing\Contact;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactQualification extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'contact_id',
        'assigned_staff_id',
        'status',
        'priority',
        'score',
        'qualification_result',
        'service_interest',
        'estimated_value',
        'budget_status',
        'budget_amount',
        'purchase_timeline',
        'decision_role',
        'qualification_note',
        'first_contacted_at',
        'last_contacted_at',
        'next_follow_up_at',
        'qualified_at',
        'qualified_by_staff_id',
        'unqualified_reason',
        'converted_at',
        'converted_customer_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContactQualificationStatus::class,
            'qualification_result' => QualificationResult::class,
            'score' => 'integer',
            'estimated_value' => 'decimal:2',
            'budget_amount' => 'decimal:2',
            'first_contacted_at' => 'datetime',
            'last_contacted_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
            'qualified_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_staff_id');
    }

    public function qualifiedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'qualified_by_staff_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ContactQualificationNote::class);
    }

    public function isConvertible(): bool
    {
        return $this->status === ContactQualificationStatus::Qualified
            && $this->qualification_result === QualificationResult::Purchased;
    }
}
