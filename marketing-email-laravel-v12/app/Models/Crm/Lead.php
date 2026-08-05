<?php

namespace App\Models\Crm;

use App\Enums\Crm\LeadIntakeStatus;
use App\Models\Marketing\Contact;
use App\Models\Marketing\LandingPageSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'lead_code',
        'submission_id',
        'contact_id',
        'company_id',
        'assigned_staff_id',
        'source',
        'source_detail',
        'title',
        'service_interest',
        'estimated_value',
        'intake_status',
        'assigned_at',
        'assigned_by_user_id',
        'converted_to_opportunity_at',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'intake_status' => LeadIntakeStatus::class,
            'estimated_value' => 'decimal:2',
            'assigned_at' => 'datetime',
            'converted_to_opportunity_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(
            LandingPageSubmission::class,
            'submission_id'
        );
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_staff_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function qualification(): HasOne
    {
        return $this->hasOne(ContactQualification::class);
    }

    public function qualificationNotes(): HasManyThrough
    {
        return $this->hasManyThrough(
            ContactQualificationNote::class,
            ContactQualification::class,
            'lead_id',
            'contact_qualification_id',
            'id',
            'id'
        );
    }
}
